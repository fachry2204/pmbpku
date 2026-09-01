<?php
namespace Tests\Feature;
use App\Models\{AdmissionPeriod,Applicant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
class AdminOperationsTest extends TestCase { use RefreshDatabase;
 private function applicant(string $selection='passed'):Applicant{$p=AdmissionPeriod::create(['name'=>'Tes '.$selection,'year'=>random_int(2026,2099),'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);return Applicant::create(['admission_period_id'=>$p->id,'registration_number'=>'PKU-'.$p->year.'-'.random_int(100000,999999),'submission_uuid'=>fake()->uuid(),'full_name'=>'Pendaftar '.$selection,'birth_place'=>'Bandung','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'628'.random_int(1000000000,9999999999),'whatsapp_display'=>'08','email'=>fake()->unique()->safeEmail(),'payment_status'=>'paid','document_status'=>'complete','selection_status'=>$selection,'consented_at'=>now(),'submitted_at'=>now()]);}
 public function test_last_active_super_admin_cannot_be_disabled():void{$admin=User::factory()->create(['username'=>'admin_test','role'=>'super_admin','is_active'=>true]);$this->actingAs($admin)->patch("/admin/users/{$admin->id}",['name'=>$admin->name,'username'=>$admin->username,'role'=>'super_admin','is_active'=>false])->assertSessionHasErrors('role');$this->assertTrue($admin->fresh()->is_active);}
 public function test_score_page_only_contains_accepted_applicants():void{$admin=User::factory()->create(['role'=>'admin_pmb','is_active'=>true]);$passed=$this->applicant('passed');$this->applicant('not_passed');$this->actingAs($admin)->get('/admin/applicant-scores')->assertOk()->assertInertia(fn(Assert $page)=>$page->component('Admin/ApplicantScores/Index')->has('applicants.data',1)->where('applicants.data.0.id',$passed->id));}
 public function test_admin_can_save_four_scores():void{$admin=User::factory()->create(['role'=>'admin_pmb','is_active'=>true]);$passed=$this->applicant();$this->actingAs($admin)->patch("/admin/applicant-scores/{$passed->id}",['score_1'=>80,'score_2'=>90,'score_3'=>70,'score_4'=>100])->assertSessionHasNoErrors();$this->assertDatabaseHas('applicant_scores',['applicant_id'=>$passed->id,'score_1'=>80,'score_4'=>100]);}
 public function test_scores_are_rejected_for_applicant_not_accepted():void{$admin=User::factory()->create(['role'=>'admin_pmb','is_active'=>true]);$applicant=$this->applicant('not_passed');$this->actingAs($admin)->patch("/admin/applicant-scores/{$applicant->id}",['score_1'=>80,'score_2'=>80,'score_3'=>80,'score_4'=>80])->assertStatus(422);$this->assertDatabaseCount('applicant_scores',0);}
 public function test_viewer_cannot_manage_scores():void{$viewer=User::factory()->create(['role'=>'viewer','is_active'=>true]);$this->actingAs($viewer)->get('/admin/applicant-scores')->assertForbidden();}
}

