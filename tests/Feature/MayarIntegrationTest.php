<?php

namespace Tests\Feature;

use App\Models\{AdmissionPeriod, Applicant, Payment};
use App\Services\Mayar\MayarClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MayarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $period = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);
        return Applicant::create(['admission_period_id' => $period->id, 'registration_number' => 'PKU-2026-000888', 'submission_uuid' => fake()->uuid(), 'full_name' => 'Mayar Test', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'whatsapp_normalized' => '6281234567890', 'whatsapp_display' => '0812', 'email' => 'mayar@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
    }

    public function test_mayar_create_invoice_uses_bearer_api_and_maps_checkout_link(): void
    {
        $applicant = $this->applicant();
        config(['services.mayar.mode' => 'sandbox', 'services.mayar.api_key' => 'mayar-secret']);
        Http::fake(['api.mayar.io/*' => Http::response(['statusCode' => 200, 'messages' => 'success', 'data' => ['id' => 'invoice-1', 'transactionId' => 'transaction-1', 'link' => 'https://merchant.myr.id/invoices/abc']], 200)]);

        $data = app(MayarClient::class)->create($applicant, 'MAYAR', 'PMB-REF-1', 250000);

        $this->assertSame('transaction-1', $data['reference']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer mayar-secret')
            && $request->url() === 'https://api.mayar.io/hl/v2/invoices/create'
            && $request['name'] === 'Mayar Test'
            && $request['items'][0]['rate'] === 250000
            && $request['extraData']['noCustomer'] === 'PMB-REF-1');
    }

    public function test_mayar_paid_webhook_is_idempotent(): void
    {
        $applicant = $this->applicant();
        $payment = Payment::create(['applicant_id' => $applicant->id, 'provider' => 'mayar', 'merchant_ref' => 'PMB-REF-2', 'provider_reference' => 'transaction-2', 'base_amount' => 250000, 'total_amount' => 250000, 'status' => 'unpaid']);
        $payload = ['event' => 'payment.received', 'data' => ['id' => 'transaction-2', 'transactionId' => 'transaction-2', 'status' => 'SUCCESS', 'transactionStatus' => 'paid', 'amount' => 250000, 'customerEmail' => 'mayar@example.test', 'updatedAt' => '2026-09-06T00:00:00.000Z']];

        $this->postJson('/webhooks/mayar', $payload)->assertOk();
        $this->postJson('/webhooks/mayar', $payload)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $applicant->fresh()->payment_status->value);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('status_histories', 1);
    }
}
