<?php

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Payment;
use App\Services\Midtrans\MidtransClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $period = AdmissionPeriod::create([
            'name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU',
            'starts_at' => now(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true,
        ]);

        return Applicant::create([
            'admission_period_id' => $period->id, 'registration_number' => 'PKU-2026-000777',
            'submission_uuid' => fake()->uuid(), 'full_name' => 'Peserta Midtrans', 'birth_place' => 'Jakarta',
            'birth_date' => '2000-01-01', 'address' => 'Jakarta', 'whatsapp_normalized' => '6281234567890',
            'whatsapp_display' => '081234567890', 'email' => 'midtrans@example.test', 'payment_status' => 'unpaid',
            'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now(),
        ]);
    }

    public function test_snap_transaction_uses_server_key_and_redirect_url(): void
    {
        $applicant = $this->applicant();
        config(['services.midtrans.mode' => 'sandbox', 'services.midtrans.server_key' => 'SB-Mid-server-test']);
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/token',
        ])]);

        $data = app(MidtransClient::class)->create($applicant, 'MIDTRANS', 'PMB-ORDER-1', 250000);

        $this->assertSame('https://app.sandbox.midtrans.com/snap/v4/redirection/token', $data['paymentUrl']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:'))
            && $request['transaction_details']['order_id'] === 'PMB-ORDER-1'
            && $request['transaction_details']['gross_amount'] === 250000
        );
    }

    public function test_signed_settlement_callback_marks_payment_paid(): void
    {
        $applicant = $this->applicant();
        $payment = Payment::create([
            'applicant_id' => $applicant->id, 'provider' => 'midtrans', 'merchant_ref' => 'PMB-ORDER-2',
            'base_amount' => 250000, 'total_amount' => 250000, 'status' => 'unpaid',
        ]);
        config(['services.midtrans.server_key' => 'SB-Mid-server-test']);
        $payload = [
            'transaction_id' => 'midtrans-transaction-id', 'transaction_status' => 'settlement',
            'status_code' => '200', 'payment_type' => 'qris', 'order_id' => 'PMB-ORDER-2',
            'gross_amount' => '250000.00', 'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash('sha512', 'PMB-ORDER-2'.'200'.'250000.00'.'SB-Mid-server-test');

        $this->postJson('/webhooks/midtrans', $payload)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('midtrans-transaction-id', $payment->fresh()->provider_reference);
        $this->assertSame('paid', $applicant->fresh()->payment_status->value);
        $this->assertDatabaseHas('payment_webhook_events', ['provider' => 'midtrans', 'signature_valid' => true]);
    }

    public function test_invalid_midtrans_signature_is_rejected(): void
    {
        config(['services.midtrans.server_key' => 'server-key']);

        $this->postJson('/webhooks/midtrans', [
            'order_id' => 'ORDER', 'status_code' => '200', 'gross_amount' => '10000.00', 'signature_key' => 'invalid',
        ])->assertForbidden();
    }
}
