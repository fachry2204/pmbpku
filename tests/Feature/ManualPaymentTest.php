<?php
namespace Tests\Feature;
use App\Models\{AdmissionPeriod,Applicant,Payment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ManualPaymentTest extends TestCase { use RefreshDatabase;
 private function applicant():Applicant{$p=AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);return Applicant::create(['admission_period_id'=>$p->id,'registration_number'=>'PKU-2026-000111','submission_uuid'=>fake()->uuid(),'full_name'=>'Pendaftar Manual','birth_place'=>'Bogor','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'6281234567000','whatsapp_display'=>'0812','email'=>'manual@example.test','payment_status'=>'unpaid','document_status'=>'pending_review','selection_status'=>'not_scheduled','consented_at'=>now(),'submitted_at'=>now()]);}
 private function submitProof(Applicant $a):Payment{Storage::fake('local');$this->post("/pembayaran/{$a->registration_number}/manual",['payment_proof'=>UploadedFile::fake()->image('bukti.jpg')])->assertSessionHas('status');return Payment::firstOrFail();}
 public function test_manual_proof_stays_private_and_finance_can_accept():void{$a=$this->applicant();$payment=$this->submitProof($a);$this->assertSame('pending',$a->fresh()->payment_status->value);Storage::disk('local')->assertExists($a->documents()->where('type','payment_proof')->firstOrFail()->path);$finance=User::factory()->create(['role'=>'finance','is_active'=>true]);$this->actingAs($finance)->patch("/admin/payments/{$payment->id}/verify",['decision'=>'accept','note'=>'Transfer cocok'])->assertSessionHasNoErrors();$this->assertSame('paid',$payment->fresh()->status);$this->assertSame('paid',$a->fresh()->payment_status->value);}
 public function test_viewer_cannot_verify_manual_payment():void{$payment=$this->submitProof($this->applicant());$viewer=User::factory()->create(['role'=>'viewer','is_active'=>true]);$this->actingAs($viewer)->patch("/admin/payments/{$payment->id}/verify",['decision'=>'accept'])->assertForbidden();}
}
