<?php
namespace Tests\Feature;
use App\Jobs\SendApplicantNotification;
use App\Models\{AdmissionPeriod,Applicant,NotificationLog};
use App\Services\Duitku\DuitkuClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class ProviderIntegrationTest extends TestCase {
 use RefreshDatabase;
 private function applicant():Applicant{$p=AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);return Applicant::create(['admission_period_id'=>$p->id,'registration_number'=>'PKU-2026-000555','submission_uuid'=>fake()->uuid(),'full_name'=>'Provider Test','birth_place'=>'Bogor','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'6281234567890','whatsapp_display'=>'0812','email'=>'provider@example.test','payment_status'=>'unpaid','document_status'=>'pending_review','selection_status'=>'not_scheduled','consented_at'=>now(),'submitted_at'=>now()]);}
 public function test_duitku_create_payload_and_signature():void{$a=$this->applicant();config(['services.duitku.mode'=>'sandbox','services.duitku.api_key'=>'secret','services.duitku.merchant_code'=>'D123']);Http::fake(['sandbox.duitku.com/*'=>Http::response(['merchantCode'=>'D123','reference'=>'REF1','amount'=>250000,'statusCode'=>'00','paymentUrl'=>'https://sandbox.duitku.com/pay/REF1'],200)]);$data=app(DuitkuClient::class)->create($a,'VC','INV1',250000);$this->assertSame('REF1',$data['reference']);Http::assertSent(fn($r)=>$r['merchantOrderId']==='INV1'&&$r['signature']===hash_hmac('sha256','D123INV1250000','secret'));}
 public function test_fonnte_job_uses_post_authorization_and_marks_sent():void{$a=$this->applicant();config(['services.fonnte.token'=>'token','services.fonnte.base_url'=>'https://api.fonnte.com','services.fonnte.country_code'=>'62']);Http::fake(['api.fonnte.com/send'=>Http::response(['status'=>true,'requestid'=>'REQ1'])]);$log=NotificationLog::create(['applicant_id'=>$a->id,'channel'=>'whatsapp','event_type'=>'test','recipient_masked'=>'6281****890','unique_key'=>'test:wa','status'=>'queued']);(new SendApplicantNotification($log->id,'Pesan tes'))->handle();Http::assertSent(fn($r)=>$r->method()==='POST'&&$r->hasHeader('Authorization','token')&&$r['target']==='6281234567890');$this->assertSame('sent',$log->fresh()->status);}
}
