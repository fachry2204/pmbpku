<?php

namespace App\Http\Controllers;

use App\Enums\SelectionStatus;
use App\Models\Applicant;
use App\Models\TestSession;
use App\Services\SettingsService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class SelectionCardController extends Controller
{
    public function publicDownload(Request $request): Response
    {
        $id = $request->session()->get('status_applicant_id');
        abort_unless($id, 403);

        return $this->download(Applicant::findOrFail($id));
    }

    public function adminDownload(Applicant $applicant): Response
    {
        return $this->download($applicant);
    }

    public function publicRegistrationDownload(Request $request): Response
    {
        $id = $request->session()->get('status_applicant_id');
        abort_unless($id, 403);

        return $this->downloadRegistration(Applicant::findOrFail($id));
    }

    private function downloadRegistration(Applicant $applicant): Response
    {
        abort_unless($applicant->payment_status->value === 'paid', 404, 'Bukti registrasi belum tersedia.');
        $applicant->load('documents');
        $pdf = Pdf::loadView('pdf.registration-proof', [
            'applicant' => $applicant,
            'logoDataUri' => $this->fileDataUri(public_path('images/logo-footer-pku.png')),
            'photoDataUri' => $this->photoDataUri($applicant),
            'registrationQrDataUri' => $this->registrationQrDataUri($applicant),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('bukti-registrasi-'.$applicant->registration_number.'.pdf');
    }

    private function download(Applicant $applicant): Response
    {
        $applicant->load(['documents', 'testSessions' => fn ($query) => $query->latest('starts_at')]);
        $session = $applicant->testSessions->first();
        abort_unless($this->isAvailable($applicant, $session), 404, 'Kartu seleksi belum tersedia.');

        $pdf = Pdf::loadView('pdf.selection-card', [
            'applicant' => $applicant,
            'session' => $session,
            'logoDataUri' => $this->fileDataUri(public_path('images/logo-pku-mui-jakarta.png')),
            'photoDataUri' => $this->photoDataUri($applicant),
            'attendanceQrDataUri' => $this->attendanceQrDataUri($applicant),
            'contactPhone' => $this->contactPhone(),
            'selectionLocation' => trim((string) app(SettingsService::class)->get('pmb.selection_location', '')),
        ])->setPaper([0, 0, 297.64, 226.77]);

        return $pdf->download('kartu-seleksi-'.$applicant->registration_number.'.pdf');
    }

    private function isAvailable(Applicant $applicant, ?TestSession $session): bool
    {
        return $session !== null && in_array($applicant->selection_status, [
            SelectionStatus::Scheduled,
            SelectionStatus::AttendingTest,
        ], true);
    }

    private function photoDataUri(Applicant $applicant): ?string
    {
        $photo = $applicant->documents->where('type', 'photo_4x6')->sortByDesc('version')->first();
        if (! $photo || $photo->disk !== 'local' || ! Storage::disk('local')->exists($photo->path)) {
            return null;
        }

        return 'data:'.($photo->mime_type ?: 'image/jpeg').';base64,'.base64_encode(Storage::disk('local')->get($photo->path));
    }

    private function fileDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return 'data:'.(mime_content_type($path) ?: 'image/png').';base64,'.base64_encode(file_get_contents($path));
    }

    private function attendanceQrDataUri(Applicant $applicant): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString(route('attendance.index', [
            'identifier' => $applicant->registration_number,
        ]));

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function registrationQrDataUri(Applicant $applicant): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd);
        $url = URL::temporarySignedRoute('status.email', now()->addMonths(6), ['applicant' => $applicant->id]);
        $svg = (new Writer($renderer))->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function contactPhone(): string
    {
        $contact = app(SettingsService::class)->get('landing.contact', 'HP: +62 898-8000-739');

        return preg_match('/(?:HP|Telp|Telepon)\s*:\s*([^\n]+)/i', $contact, $match) ? trim($match[1]) : '+62 898-8000-739';
    }
}
