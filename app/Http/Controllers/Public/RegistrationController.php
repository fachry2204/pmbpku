<?php

namespace App\Http\Controllers\Public;

use App\Actions\QueueApplicantNotification;
use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicantRequest;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Services\PaymentGatewayService;
use App\Services\SettingsService;
use App\Support\IndonesianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegistrationController extends Controller
{
    public function create(PaymentGatewayService $gateway, SettingsService $settings): Response
    {
        $channels = [];
        $paymentError = null;
        $amount = (int) $settings->get('pmb.registration_fee', 250000);
        try {
            $channels = Cache::remember('payment.channels.'.$gateway->provider().'.'.$gateway->mode().'.'.$amount, 300, fn () => $gateway->channels($amount));
        } catch (Throwable $exception) {
            report($exception);
            $message = $exception->getMessage();
            $paymentError = str_starts_with($message, 'Tripay:') || str_starts_with($message, 'Mayar Link:')
                ? $message
                : 'Metode pembayaran belum tersedia. Silakan hubungi panitia.';
        }

        return Inertia::render('Public/Register', [
            'channels' => $channels,
            'paymentError' => $paymentError,
            'registrationFee' => $amount,
            'documentUploadEnabled' => ! $settings->get('registration.document_upload_disabled', false),
            'maxTotalUploadBytes' => $this->maxTotalUploadBytes(),
        ]);
    }

    public function store(StoreApplicantRequest $request, QueueApplicantNotification $notifications, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();
        $phone = IndonesianPhone::normalize($data['whatsapp']);
        $registrationYear = (int) $settings->get('pmb.registration_year', 0);
        $storedPaths = [];

        try {
            $applicant = DB::transaction(function () use ($request, $data, $phone, $registrationYear, &$storedPaths) {
                $period = AdmissionPeriod::where('is_active', true)->lockForUpdate()->firstOrFail();
                $year = $registrationYear ?: (int) $period->year;
                $yearPrefix = $period->registration_prefix.'-'.$year.'-%';
                if ($existing = Applicant::where('admission_period_id', $period->id)->where('submission_uuid', $data['submission_uuid'])->first()) {
                    return $existing;
                }
                if (Applicant::where('admission_period_id', $period->id)->where('registration_number', 'like', $yearPrefix)->where('email', strtolower($data['email']))->exists()) {
                    throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar pada periode PMB aktif. Silakan gunakan menu Cek Status Pendaftaran.']);
                }
                if (Applicant::where('admission_period_id', $period->id)->where('registration_number', 'like', $yearPrefix)->where('whatsapp_normalized', $phone)->exists()) {
                    throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp ini sudah terdaftar pada periode PMB aktif. Silakan gunakan menu Cek Status Pendaftaran.']);
                }
                $registrationNumber = $this->nextRegistrationNumber($period, $year);
                $applicant = Applicant::create(['admission_period_id' => $period->id, 'registration_number' => $registrationNumber, 'submission_uuid' => $data['submission_uuid'], 'full_name' => $data['full_name'], 'birth_place' => $data['birth_place'], 'birth_date' => $data['birth_date'], 'address' => $data['address'], 'whatsapp_normalized' => $phone, 'whatsapp_display' => $data['whatsapp'], 'email' => strtolower($data['email']), 'payment_status' => PaymentStatus::Unpaid, 'document_status' => DocumentStatus::PendingReview, 'selection_status' => SelectionStatus::NotScheduled, 'consented_at' => now(), 'submitted_at' => now(), 'lookup_secret_hash' => Hash::make(Str::random(48))]);
                foreach (['recommendation_letter', 'diploma', 'photo_4x6', 'identity_card', 'pddikti_screenshot'] as $type) {
                    if (! $request->hasFile($type)) {
                        continue;
                    }

                    $file = $request->file($type);
                    $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin'));
                    $path = $file->storeAs($applicant->storageDirectory(), Str::uuid().'.'.$extension, 'local');
                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException("Berkas {$type} gagal disimpan.");
                    }
                    $storedPaths[] = $path;

                    $sha256 = hash_file('sha256', $file->getRealPath());
                    if ($sha256 === false) {
                        throw new \RuntimeException("Berkas {$type} gagal dibaca.");
                    }

                    $applicant->documents()->create([
                        'type' => $type,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                        'mime_type' => Str::limit((string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream'), 100, ''),
                        'extension' => Str::limit($extension, 10, ''),
                        'size' => max(0, (int) $file->getSize()),
                        'sha256' => $sha256,
                    ]);
                }

                return $applicant;
            });
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            report($exception);

            throw ValidationException::withMessages([
                'registration' => 'Pendaftaran belum dapat disimpan. Periksa kembali dokumen, lalu coba kirim ulang. Jika masih gagal, hubungi panitia.',
            ]);
        }
        // Registration is already committed at this point. A temporary SMTP,
        // WhatsApp, or synchronous queue failure must never turn a successfully
        // stored registration into a 500 response for the applicant.
        try {
            $notifications->execute($applicant, 'registration_created', "Pendaftaran {$applicant->registration_number} berhasil diterima. Simpan nomor pendaftaran Anda.");
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('registration.success', ['registrationNumber' => $applicant->registration_number]);
    }

    public function success(string $registrationNumber, SettingsService $settings): Response
    {
        $applicant = Applicant::where('registration_number', $registrationNumber)->firstOrFail();

        return Inertia::render('Public/Success', ['applicant' => ['registration_number' => $applicant->registration_number, 'full_name' => $applicant->full_name, 'payment_status' => $applicant->payment_status], 'mayarLinkUrl' => $settings->get('payment.provider', 'duitku') === 'mayar_link' ? route('payment.mayar-link.redirect', $applicant->registration_number) : null]);
    }

    private function maxTotalUploadBytes(): int
    {
        $postMax = $this->iniBytes((string) ini_get('post_max_size'));
        $configuredMaximum = 10 * 1024 * 1024;

        if ($postMax <= 0) {
            return $configuredMaximum;
        }

        // Sisakan ruang untuk field teks dan overhead multipart/form-data.
        return max(1024 * 1024, min($configuredMaximum, $postMax - 512 * 1024));
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function nextRegistrationNumber(AdmissionPeriod $period, int $year): string
    {
        // The active period is locked by the caller. The reservation survives
        // applicant deletion, so a registration number can never be reused.
        $this->reserveExistingNumbers($period, $year);
        $lastSequence = (int) (DB::table('registration_number_reservations')
            ->where('registration_prefix', $period->registration_prefix)
            ->where('registration_year', $year)
            ->lockForUpdate()
            ->max('sequence') ?? 0);
        $sequence = $lastSequence + 1;
        $registrationNumber = sprintf('%s-%d-%06d', $period->registration_prefix, $year, $sequence);

        DB::table('registration_number_reservations')->insert([
            'admission_period_id' => $period->id,
            'registration_prefix' => $period->registration_prefix,
            'registration_year' => $year,
            'sequence' => $sequence,
            'registration_number' => $registrationNumber,
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $registrationNumber;
    }

    private function reserveExistingNumbers(AdmissionPeriod $period, int $year): void
    {
        Applicant::withTrashed()
            ->where('admission_period_id', $period->id)
            ->where('registration_number', 'like', $period->registration_prefix.'-'.$year.'-%')
            ->get(['id', 'registration_number', 'created_at'])
            ->each(function (Applicant $applicant) use ($period, $year): void {
                if (! preg_match('/-(\d+)$/', $applicant->registration_number, $matches)) {
                    return;
                }

                DB::table('registration_number_reservations')->insertOrIgnore([
                    'admission_period_id' => $period->id,
                    'registration_prefix' => $period->registration_prefix,
                    'registration_year' => $year,
                    'sequence' => (int) $matches[1],
                    'registration_number' => $applicant->registration_number,
                    'issued_at' => $applicant->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
}
