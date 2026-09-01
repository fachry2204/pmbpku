<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminAccessTest extends TestCase { use RefreshDatabase; public function test_guest_cannot_open_admin_dashboard():void{$this->get('/admin/dashboard')->assertRedirect('/login');} public function test_inactive_admin_is_denied():void{$user=User::factory()->create(['is_active'=>false,'role'=>'super_admin']);$this->actingAs($user)->get('/admin/dashboard')->assertForbidden();} public function test_active_viewer_can_read_dashboard():void{$user=User::factory()->create(['is_active'=>true,'role'=>'viewer']);$this->actingAs($user)->get('/admin/dashboard')->assertOk();} }
