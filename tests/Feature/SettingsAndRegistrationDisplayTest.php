<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Duitku\DuitkuClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class SettingsAndRegistrationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_receives_the_configured_fee(): void
    {
        Setting::create(['group' => 'pmb', 'key' => 'pmb.registration_fee', 'value' => '375000', 'type' => 'integer', 'is_encrypted' => false]);
        $this->mock(DuitkuClient::class, fn (MockInterface $mock) => $mock->shouldReceive('channels')->once()->with(375000)->andReturn([]));

        $this->get('/pendaftaran')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Public/Register')->where('registrationFee', 375000)
        );
    }

    public function test_empty_smtp_port_does_not_prevent_payment_settings_from_being_saved(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->put('/admin/settings', [
            'pmb_registration_fee' => 375000,
            'duitku_mode' => 'sandbox',
            'duitku_merchant_code' => 'D12345',
            'duitku_api_key' => 'secret-key',
            'mail_port' => 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'pmb.registration_fee', 'value' => '375000']);
        $this->assertDatabaseHas('settings', ['key' => 'duitku.merchant_code', 'value' => 'D12345']);
    }
}
