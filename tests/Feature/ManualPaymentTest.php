<?php
namespace Tests\Feature;
use App\Models\{AdmissionPeriod,Applicant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ManualPaymentTest extends TestCase { use RefreshDatabase;
 private function applicant():Applicant{$p=AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);return Applicant::create(['admission_period_id'=>$p->id,'registration_number'=>'PKU-2026-000111','submission_uuid'=>fake()->uuid(),'full_name'=>'Pendaftar Manual','birth_place'=>'Bogor','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'6281234567000','whatsapp_display'=>'0812','email'=>'manual@example.test','payment_status'=>'unpaid','document_status'=>'pending_review','selection_status'=>'not_scheduled','consented_at'=>now(),'submitted_at'=>now()]);}
 public function test_public_manual_payment_endpoint_is_not_available():void{$a=$this->applicant();$this->post("/pembayaran/{$a->registration_number}/manual",['payment_proof'=>UploadedFile::fake()->image('bukti.jpg')])->assertNotFound();$this->assertDatabaseCount('payments',0);$this->assertDatabaseCount('applicant_documents',0);}
}
