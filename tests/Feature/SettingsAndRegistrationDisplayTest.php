<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class SettingsAndRegistrationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_settings_with_both_gateway_urls(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Admin/Settings/Index')
                ->where('callbackUrl', route('webhooks.duitku'))
                ->where('tripayCallbackUrl', url('/webhooks/tripay'))
        );
    }

    public function test_settings_page_survives_an_unreadable_encrypted_credential(): void
    {
        Setting::create(['group' => 'tripay', 'key' => 'tripay.private_key', 'value' => 'broken-ciphertext', 'type' => 'string', 'is_encrypted' => true]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertInertia(
            fn (Assert $page) => $page->where('unreadableSettings.0', 'tripay.private_key')
        );
    }

    public function test_registration_page_receives_the_configured_fee(): void
    {
        Setting::create(['group' => 'pmb', 'key' => 'pmb.registration_fee', 'value' => '375000', 'type' => 'integer', 'is_encrypted' => false]);
        $this->mock(PaymentGatewayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('provider')->andReturn('duitku');
            $mock->shouldReceive('mode')->andReturn('sandbox');
            $mock->shouldReceive('channels')->once()->with(375000)->andReturn([]);
        });

        $this->get('/pendaftaran')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Public/Register')->where('registrationFee', 375000)
        );
    }

    public function test_empty_smtp_port_does_not_prevent_payment_settings_from_being_saved(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->put('/admin/settings', [
            'pmb_registration_fee' => 375000,
            'payment_provider' => 'duitku',
            'notifications_whatsapp_enabled' => true,
            'notifications_email_enabled' => true,
            'duitku_mode' => 'sandbox',
            'duitku_merchant_code' => 'D12345',
            'duitku_api_key' => 'secret-key',
            'mail_port' => 0,
            'mail_from_name' => 'Panitia PMB PKU',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'pmb.registration_fee', 'value' => '375000']);
        $this->assertDatabaseHas('settings', ['key' => 'duitku.merchant_code', 'value' => 'D12345']);
        $this->assertDatabaseHas('settings', ['key' => 'mail.from_name', 'value' => 'Panitia PMB PKU']);
    }

    public function test_super_admin_can_send_a_test_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->post('/admin/settings/test-email', [
            'test_email_recipient' => 'panitia@example.test',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
    }
}
