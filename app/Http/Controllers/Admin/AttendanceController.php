<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Support\IndonesianPhone;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function adminIndex(SettingsService $settings): Response
    {
        $applicants = $this->scheduledApplicants();
        $rows = $applicants->map(fn (Applicant $applicant) => $this->attendanceRow($applicant, $settings));
        $present = $rows->where('is_present', true)->count();

        return Inertia::render('Admin/Attendance/List', [
            'participants' => $rows->values(),
            'stats' => [
                'total' => $rows->count(),
                'present' => $present,
                'absent' => $rows->count() - $present,
            ],
        ]);
    }

    public function downloadPdf(SettingsService $settings): HttpResponse
    {
        $participants = $this->scheduledApplicants()
            ->map(fn (Applicant $applicant) => $this->attendanceRow($applicant, $settings));
        $testDates = $participants->pluck('test_date')->filter()->unique()->values();
        $testDate = $testDates->count() === 1 ? $testDates->first() : ($testDates->isEmpty() ? '-' : 'Sesuai jadwal masing-masing');
        $registrationYear = (int) $settings->get('pmb.registration_year', now()->year);

        $pdf = Pdf::loadView('pdf.attendance-list', [
            'participants' => $participants,
            'testDate' => $testDate,
            'registrationYear' => $registrationYear,
            'logoDataUri' => $this->fileDataUri(public_path('images/logo-footer-pku.png')),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('absensi-calon-mahasiswa-pku-'.$registrationYear.'.pdf');
    }

    public function index(Request $request, SettingsService $settings): Response
    {
        $identifier = trim($request->string('identifier')->limit(190)->toString());
        $applicant = $identifier !== '' ? $this->findApplicant($identifier) : null;

        if ($applicant) {
            $applicant->load(['documents', 'testSessions' => fn ($query) => $query->latest('starts_at')]);
        }

        $session = $applicant?->testSessions->first();
        $photo = $applicant?->documents->where('type', 'photo_4x6')->sortByDesc('version')->first();

        return Inertia::render('Admin/Attendance/Index', [
            'identifier' => $identifier,
            'notFound' => $identifier !== '' && ! $applicant,
            'applicant' => $applicant ? [
                'id' => $applicant->id,
                'registration_number' => $applicant->registration_number,
                'full_name' => $applicant->full_name,
                'email' => $applicant->email,
                'whatsapp' => $applicant->whatsapp_display,
                'selection_status' => $applicant->selection_status->value,
                'photo_url' => $photo ? route('admin.documents.view', $photo) : null,
                'schedule' => $session ? [
                    'date' => $session->starts_at->locale('id')->translatedFormat('l, d F Y'),
                    'time' => $session->starts_at->format('H:i').' WIB',
                    'location' => trim((string) $settings->get('pmb.selection_location', '')) ?: $session->location ?: 'Lokasi belum ditentukan',
                    'attendance_status' => $session->pivot->attendance_status,
                    'attended_at' => $session->pivot->attended_at
                        ? Carbon::parse($session->pivot->attended_at)->locale('id')->translatedFormat('d F Y, H:i').' WIB'
                        : null,
                ] : null,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['applicant_id' => ['required', 'ulid', 'exists:applicants,id']]);

        DB::transaction(function () use ($request, $data): void {
            $applicant = Applicant::whereKey($data['applicant_id'])->lockForUpdate()->firstOrFail();
            if ($applicant->selection_status === SelectionStatus::AttendingTest) {
                return;
            }
            if ($applicant->selection_status !== SelectionStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'applicant_id' => 'Peserta belum berstatus Seleksi Terjadwal sehingga belum dapat diabsen.',
                ]);
            }

            $session = $applicant->testSessions()->latest('starts_at')->first();
            if (! $session) {
                throw ValidationException::withMessages(['applicant_id' => 'Jadwal seleksi peserta tidak ditemukan.']);
            }

            $session->applicants()->updateExistingPivot($applicant->id, [
                'attendance_status' => 'attended',
                'attended_at' => now(),
            ]);
            $applicant->update(['selection_status' => SelectionStatus::AttendingTest]);

            DB::table('status_histories')->insert([
                'applicant_id' => $applicant->id,
                'dimension' => 'selection',
                'from_status' => SelectionStatus::Scheduled->value,
                'to_status' => SelectionStatus::AttendingTest->value,
                'note' => 'Kehadiran seleksi dikonfirmasi melalui halaman absensi.',
                'changed_by_type' => 'user',
                'changed_by_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'applicant.selection.attendance_confirmed',
                'auditable_type' => Applicant::class,
                'auditable_id' => $applicant->id,
                'before_json' => json_encode(['selection_status' => SelectionStatus::Scheduled->value]),
                'after_json' => json_encode(['selection_status' => SelectionStatus::AttendingTest->value, 'attended_at' => now()->toIso8601String()]),
                'ip' => $request->ip(),
                'user_agent' => str($request->userAgent())->limit(500),
                'created_at' => now(),
            ]);
        });

        $applicant = Applicant::findOrFail($data['applicant_id']);

        return redirect()->route('attendance.index', ['identifier' => $applicant->registration_number])
            ->with('success', 'Kehadiran peserta berhasil dikonfirmasi.');
    }

    private function findApplicant(string $identifier): ?Applicant
    {
        $normalized = strtoupper((string) preg_replace('/\s+/', '', $identifier));
        $applicant = Applicant::whereRaw('UPPER(registration_number) = ?', [$normalized])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($identifier)])
            ->first();
        if ($applicant) {
            return $applicant;
        }

        try {
            return Applicant::where('whatsapp_normalized', IndonesianPhone::normalize($identifier))->first();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function scheduledApplicants()
    {
        return Applicant::query()
            ->whereIn('selection_status', [SelectionStatus::Scheduled->value, SelectionStatus::AttendingTest->value])
            ->whereHas('testSessions')
            ->with(['testSessions' => fn ($query) => $query->orderByDesc('starts_at')])
            ->orderBy('registration_number')
            ->get();
    }

    private function attendanceRow(Applicant $applicant, SettingsService $settings): array
    {
        $session = $applicant->testSessions->first();
        $isPresent = $session?->pivot?->attendance_status === 'attended'
            || $applicant->selection_status === SelectionStatus::AttendingTest;

        return [
            'id' => $applicant->id,
            'registration_number' => $applicant->registration_number,
            'full_name' => $applicant->full_name,
            'whatsapp' => $applicant->whatsapp_display,
            'test_date' => $session?->starts_at?->locale('id')->translatedFormat('d F Y'),
            'test_time' => $session?->starts_at ? $session->starts_at->format('H:i').' WIB' : null,
            'location' => trim((string) $settings->get('pmb.selection_location', '')) ?: $session?->location ?: 'Lokasi belum ditentukan',
            'is_present' => $isPresent,
            'attendance_status' => $isPresent ? 'Ikut Seleksi' : 'Belum Absen',
            'attended_at' => $session?->pivot?->attended_at
                ? Carbon::parse($session->pivot->attended_at)->locale('id')->translatedFormat('d F Y, H:i').' WIB'
                : null,
        ];
    }

    private function fileDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return 'data:'.(mime_content_type($path) ?: 'image/png').';base64,'.base64_encode(file_get_contents($path));
    }
}
