<?php

namespace Tests\Feature;

use App\Models\{AdmissionPeriod, Applicant, Payment};
use App\Services\Duitku\DuitkuSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuitkuWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $period = AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);
        return Applicant::create(['admission_period_id'=>$period->id,'registration_number'=>'PKU-2026-000777','submission_uuid'=>fake()->uuid(),'full_name'=>'Callback Test','birth_place'=>'Jakarta','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'6281234567890','whatsapp_display'=>'0812','email'=>'callback@example.test','payment_status'=>'unpaid','document_status'=>'pending_review','selection_status'=>'not_scheduled','consented_at'=>now(),'submitted_at'=>now()]);
    }

    public function test_invalid_signature_does_not_change_payment(): void
    {
        $applicant = $this->applicant();
        $payment = Payment::create(['applicant_id'=>$applicant->id,'provider'=>'duitku','merchant_ref'=>'INV-1','provider_reference'=>'REF-1','base_amount'=>250000,'total_amount'=>250000,'status'=>'unpaid']);
        config(['services.duitku.merchant_code'=>'D123','services.duitku.api_key'=>'secret']);
        $this->post('/webhooks/duitku',['merchantCode'=>'D123','amount'=>250000,'merchantOrderId'=>'INV-1','reference'=>'REF-1','resultCode'=>'00','signature'=>'invalid'])->assertForbidden();
        $this->assertSame('unpaid', $payment->fresh()->status);
    }

    public function test_paid_callback_is_idempotent(): void
    {
        $applicant = $this->applicant();
        $payment = Payment::create(['applicant_id'=>$applicant->id,'provider'=>'duitku','merchant_ref'=>'INV-2','provider_reference'=>'REF-2','base_amount'=>250000,'total_amount'=>250000,'status'=>'unpaid']);
        config(['services.duitku.merchant_code'=>'D123','services.duitku.api_key'=>'secret']);
        $payload = ['merchantCode'=>'D123','amount'=>250000,'merchantOrderId'=>'INV-2','reference'=>'REF-2','paymentCode'=>'VA','resultCode'=>'00'];
        $payload['signature'] = DuitkuSignature::callback('D123', 250000, 'INV-2', 'secret');
        $this->post('/webhooks/duitku', $payload)->assertOk();
        $this->post('/webhooks/duitku', $payload)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $applicant->fresh()->payment_status->value);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('status_histories', 1);
    }
}
