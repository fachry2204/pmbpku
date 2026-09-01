<?php

namespace App\Jobs;

use App\Models\ApplicantDocument;
use App\Services\RcloneStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SyncApplicantDocumentToDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 360;

    public function __construct(public string $documentId) {}

    public function handle(RcloneStorageService $drive): void
    {
        if (! $drive->enabled()) {
            return;
        }

        $document = ApplicantDocument::with('applicant')->find($this->documentId);
        if (! $document || $document->disk !== 'local' || ! Storage::disk('local')->exists($document->path)) {
            return;
        }

        $extension = $document->extension ?: pathinfo($document->path, PATHINFO_EXTENSION);
        $filename = $document->type.'-'.$document->id.($extension ? '.'.$extension : '');
        $destination = $drive->destination($document->applicant->registration_number, $filename);
        $drive->uploadLocal($document->path, $destination);

        // Pas foto tetap lokal agar dapat ditampilkan cepat, tetapi salinannya
        // tetap tersedia di Google Drive sebagai cadangan.
        if ($document->type === 'photo_4x6') {
            return;
        }

        $localPath = $document->path;
        $document->update(['disk' => 'rclone', 'path' => $destination]);
        Storage::disk('local')->delete($localPath);
    }
}
