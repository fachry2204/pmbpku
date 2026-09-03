<?php
namespace Tests\Feature;
use App\Models\{AdmissionPeriod,Applicant,User}; use App\Services\SettingsService; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class PreviewLocationFallbackTest extends TestCase { use RefreshDatabase; public function test_preview(): void {
$p=AdmissionPeriod::create(['name'=>'PMB 2026','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>10000,'is_active'=>true]);
$a=Applicant::create(['admission_period_id'=>$p->id,'registration_number'=>'PKU-2026-000001','submission_uuid'=>fake()->uuid(),'full_name'=>'Fachry Hidayat','birth_place'=>'Jakarta','birth_date'=>'1988-04-22','address'=>'Jakarta','whatsapp_normalized'=>'62812121212','whatsapp_display'=>'0812121212','email'=>'fachry@example.com','payment_status'=>'paid','document_status'=>'complete','selection_status'=>'scheduled','consented_at'=>now(),'submitted_at'=>now()]);
$a->testSessions()->create(['admission_period_id'=>$p->id,'name'=>'Seleksi','starts_at'=>'2026-09-17 23:31:00','location'=>null],['attendance_status'=>'assigned','assigned_at'=>now()]);
app(SettingsService::class)->put('pmb','pmb.selection_location','LOKASI SAAT INI TES');
$u=User::factory()->create(['role'=>'admin_pmb','is_active'=>true]); $r=$this->actingAs($u)->get("/admin/applicants/{$a->id}/selection-card"); $r->assertOk(); file_put_contents(base_path('output/pdf/kartu-seleksi-preview.pdf'),$r->getContent()); }}
