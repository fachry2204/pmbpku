<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use App\Services\RcloneStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrivateDocumentController extends Controller
{
    public function __invoke(Request $request, ApplicantDocument $document, RcloneStorageService $drive): Response
    {
        return $this->download($request, $document, $drive);
    }

    public function download(Request $request, ApplicantDocument $document, RcloneStorageService $drive): Response
    {
        return $this->respond($request, $document, true, $drive);
    }

    public function inline(Request $request, ApplicantDocument $document, RcloneStorageService $drive): Response
    {
        abort_unless($document->type === 'photo_4x6', 404);

        return $this->respond($request, $document, false, $drive);
    }

    private function respond(Request $request, ApplicantDocument $document, bool $attachment, RcloneStorageService $drive): Response
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'document.download',
                'auditable_type' => ApplicantDocument::class,
                'auditable_id' => $document->id,
                'ip' => $request->ip(),
                'user_agent' => str($request->userAgent())->limit(500),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Kegagalan audit tidak boleh membuat dokumen yang sah gagal diunduh.
            report($exception);
        }

        $filename = preg_replace('/[\x00-\x1F\x7F"]+/u', '_', basename($document->original_name ?: 'dokumen'));
        $filename = str_replace('\\', '_', $filename ?: 'dokumen');
        $mime = $document->mime_type ?: 'application/octet-stream';

        if ($document->disk === 'rclone') {
            $temporary = $drive->downloadToTemporaryFile($document->path);
            $headers = ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0'];

            return response()->download($temporary, $filename, $headers)->deleteFileAfterSend(true);
        }

        $disk = Storage::disk($document->disk ?: 'local');
        abort_unless($disk->exists($document->path), 404, 'File dokumen tidak ditemukan di penyimpanan server.');
        $mime = $document->mime_type ?: $disk->mimeType($document->path) ?: 'application/octet-stream';

        $headers = ['Content-Type' => $mime, 'Content-Length' => (string) $disk->size($document->path), 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0'];
        if (! $attachment) {
            $headers['Content-Disposition'] = 'inline; filename="'.$filename.'"';
        }
        $callback = function () use ($disk, $document): void {
            $stream = $disk->readStream($document->path);
            abort_if($stream === false, 404, 'File dokumen tidak dapat dibaca.');
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        };

        return $attachment ? response()->streamDownload($callback, $filename, $headers) : response()->stream($callback, 200, $headers);
    }
}
