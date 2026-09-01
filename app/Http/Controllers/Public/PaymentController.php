<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaymentController extends Controller
{
    private function amount(Applicant $applicant, SettingsService $settings): int
    {
        return (int) $settings->get('pmb.registration_fee', $applicant->admissionPeriod->registration_fee);
    }

    public function show(Request $request, string $registrationNumber, PaymentGatewayService $gateway, SettingsService $settings): Response
    {
        $applicant = Applicant::with('admissionPeriod')->where('registration_number', $registrationNumber)->firstOrFail();
        $channels = [];
        $error = null;
        try {
            $amount = $this->amount($applicant, $settings);
            $channels = Cache::remember('payment.channels.'.$gateway->provider().'.'.$gateway->mode().'.'.$amount, 300, fn () => $gateway->channels($amount));
        } catch (Throwable) {
            $error = 'Channel pembayaran sedang tidak tersedia.';
        }

        return Inertia::render('Public/Payment', [
            'applicant' => ['registration_number' => $applicant->registration_number, 'full_name' => $applicant->full_name, 'payment_status' => $applicant->payment_status],
            'registrationFee' => $this->amount($applicant, $settings),
            'channels' => $channels,
            'error' => $error,
            'selectedMethod' => $request->string('method')->toString(),
            'registered' => $request->boolean('registered'),
        ]);
    }

    public function create(Request $request, string $registrationNumber, PaymentGatewayService $gateway, SettingsService $settings): RedirectResponse
    {
        $data = $request->validate(['method' => ['required', 'string', 'max:30']]);
        $applicant = Applicant::with('admissionPeriod')->where('registration_number', $registrationNumber)->firstOrFail();
        if ($applicant->payment_status->value === 'paid') {
            return back()->with('status', 'Pembayaran sudah lunas.');
        }
        $merchantRef = 'PMB-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
        $amount = $this->amount($applicant, $settings);
        $provider = $gateway->provider();
        try {
            $remote = $gateway->create($applicant, $data['method'], $merchantRef, $amount);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'method' => app()->isProduction()
                    ? 'Transaksi pembayaran belum dapat dibuat. Periksa kembali metode pembayaran atau hubungi panitia.'
                    : $exception->getMessage(),
            ]);
        }
        $isTripay = $provider === 'tripay';
        $payment = DB::transaction(fn () => Payment::create([
            'applicant_id' => $applicant->id, 'provider' => $provider, 'merchant_ref' => $merchantRef,
            'provider_reference' => $remote['reference'], 'payment_method' => $remote[$isTripay ? 'payment_method' : 'paymentMethod'] ?? $data['method'],
            'base_amount' => $amount, 'fee_merchant' => (int) ($remote['fee_merchant'] ?? 0),
            'fee_customer' => (int) ($remote['fee_customer'] ?? 0), 'total_amount' => (int) ($remote['amount'] ?? $amount) + (int) ($isTripay ? ($remote['fee_customer'] ?? 0) : 0),
            'status' => 'unpaid', 'checkout_url' => $remote[$isTripay ? 'checkout_url' : 'paymentUrl'] ?? null,
            'instructions_json' => isset($remote[$isTripay ? 'pay_code' : 'vaNumber']) ? ['va_number' => $remote[$isTripay ? 'pay_code' : 'vaNumber']] : null,
            'expires_at' => now()->addDay(),
            'response_payload_redacted' => array_intersect_key($remote, array_flip(['merchantCode', 'reference', 'paymentUrl', 'checkout_url', 'vaNumber', 'pay_code', 'amount', 'statusCode', 'statusMessage'])),
        ]));
        $applicant->update(['payment_status' => 'pending']);
        if (! $payment->checkout_url) {
            throw ValidationException::withMessages(['method' => 'Tautan pembayaran belum tersedia. Silakan coba kembali.']);
        }

        return redirect()->away($payment->checkout_url);
    }

    public function manual(Request $request, string $registrationNumber, SettingsService $settings): RedirectResponse
    {
        $request->validate(['payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $applicant = Applicant::with('admissionPeriod')->where('registration_number', $registrationNumber)->firstOrFail();
        abort_if($applicant->payment_status->value === 'paid', 422, 'Pembayaran sudah lunas.');
        $file = $request->file('payment_proof');
        $amount = $this->amount($applicant, $settings);
        DB::transaction(function () use ($applicant, $file, $amount) {
            $path = $file->storeAs($applicant->storageDirectory(), Str::uuid().'.'.$file->guessExtension(), 'local');
            $applicant->documents()->create(['type' => 'payment_proof', 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'extension' => $file->guessExtension(), 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'verification_status' => 'pending']);
            Payment::create(['applicant_id' => $applicant->id, 'provider' => 'manual', 'merchant_ref' => 'MANUAL-'.Str::upper(Str::random(16)), 'base_amount' => $amount, 'total_amount' => $amount, 'status' => 'pending']);
            $applicant->update(['payment_status' => 'pending']);
        });

        return back()->with('status', 'Bukti pembayaran diterima dan menunggu verifikasi finance.');
    }
}
