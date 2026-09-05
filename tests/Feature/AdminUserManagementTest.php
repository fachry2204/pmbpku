<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_active_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->from('/admin/users')->post('/admin/users', ['name' => 'Hartono PMB', 'username' => 'hartono_pmb', 'email' => 'hartono@example.test', 'role' => 'admin_pmb', 'password' => 'PasswordAman123', 'password_confirmation' => 'PasswordAman123'])->assertRedirect('/admin/users')->assertSessionHas('success', 'Pengguna admin dibuat.');
        $this->assertDatabaseHas('users', ['name' => 'Hartono PMB', 'username' => 'hartono_pmb', 'email' => 'hartono@example.test', 'role' => 'admin_pmb', 'is_active' => true, 'must_change_password' => true]);
    }

    public function test_duplicate_username_returns_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        User::factory()->create(['username' => 'hartono']);
        $this->actingAs($admin)->from('/admin/users')->post('/admin/users', ['name' => 'Hartono Baru', 'username' => 'hartono', 'email' => 'baru@example.test', 'role' => 'viewer', 'password' => 'PasswordAman123', 'password_confirmation' => 'PasswordAman123'])->assertRedirect('/admin/users')->assertSessionHasErrors('username');
    }
}
