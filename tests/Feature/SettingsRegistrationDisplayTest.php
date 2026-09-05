<?php

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Setting;
use App\Models\TestSession;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class SettingsRegistrationDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_settings_with_both_gateway_urls(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Settings/Index')->where('callbackUrl', route('webhooks.duitku'))->where('tripayCallbackUrl', url('/webhooks/tripay')));
    }

    public function test_super_admin_can_upload_mayar_agq_api_key(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $file = UploadedFile::fake()->createWithContent('mayar-credentials.AGQ', "MAYAR_API_KEY=mayar-key-".str_repeat('x', 700));

        $this->actingAs($admin)
            ->post('/admin/settings/mayar-key', ['mayar_key_file' => $file])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('mayar-key-'.str_repeat('x', 700), Setting::where('key', 'mayar.api_key')->firstOrFail()->getDecodedValue());
    }

    public function test_settings_page_survives_an_unreadable_encrypted_credential(): void
    {
        Setting::create(['group' => 'tripay', 'key' => 'tripay.private_key', 'value' => 'broken-ciphertext', 'type' => 'string', 'is_encrypted' => true]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertInertia(fn (Assert $page) => $page->where('unreadableSettings.0', 'tripay.private_key'));
    }

    public function test_registration_page_receives_the_configured_fee(): void
    {
        Setting::create(['group' => 'pmb', 'key' => 'pmb.registration_fee', 'value' => '375000', 'type' => 'integer', 'is_encrypted' => false]);
        $this->mock(PaymentGatewayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('provider')->andReturn('duitku');
            $mock->shouldReceive('mode')->andReturn('sandbox');
            $mock->shouldReceive('channels')->once()->with(375000)->andReturn([]);
        });
        $this->get('/pendaftaran')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/Register')->where('registrationFee', 375000));
    }

    public function test_registration_year_can_be_saved_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->put('/admin/settings', ['pmb_registration_fee' => 250000, 'pmb_registration_year' => 2030, 'registration_document_upload_disabled' => false, 'payment_provider' => 'duitku', 'notifications_whatsapp_enabled' => true, 'notifications_email_enabled' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'pmb.registration_year', 'value' => '2030']);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('registrationYear', 2030));
    }

    public function test_selection_location_can_be_saved_from_registration_settings(): void
    {
        $period = AdmissionPeriod::create(['name' => 'PMB 2026', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'registration_fee' => 250000, 'is_active' => true]);
        $session = TestSession::create(['admission_period_id' => $period->id, 'name' => 'Seleksi Lama', 'starts_at' => now()->addDay(), 'location' => null]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->put('/admin/settings', ['pmb_registration_fee' => 250000, 'pmb_selection_location' => 'Aula Utama MUI Provinsi DKI Jakarta', 'registration_document_upload_disabled' => false, 'payment_provider' => 'duitku', 'notifications_whatsapp_enabled' => true, 'notifications_email_enabled' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'pmb.selection_location', 'value' => 'Aula Utama MUI Provinsi DKI Jakarta']);
        $this->assertSame('Aula Utama MUI Provinsi DKI Jakarta', $session->fresh()->location);
    }

    public function test_selection_location_updates_an_existing_upcoming_schedule_location(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $period = AdmissionPeriod::create([
            'name' => 'PMB 2026',
            'year' => 2026,
            'registration_prefix' => 'PKU',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'registration_fee' => 250000,
            'is_active' => true,
        ]);
        $session = TestSession::create([
            'admission_period_id' => $period->id,
            'name' => 'Seleksi Mendatang',
            'starts_at' => now()->addDay(),
            'location' => 'Informasi panitia',
        ]);

        $this->actingAs($admin)->put('/admin/settings', [
            'pmb_registration_fee' => 250000,
            'pmb_selection_location' => 'LOKASI SAAT INI TES',
            'registration_document_upload_disabled' => false,
            'payment_provider' => 'duitku',
            'notifications_whatsapp_enabled' => true,
            'notifications_email_enabled' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('LOKASI SAAT INI TES', $session->fresh()->location);
    }

    public function test_empty_smtp_port_does_not_prevent_payment_settings_from_being_saved(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->put('/admin/settings', ['pmb_registration_fee' => 375000, 'registration_document_upload_disabled' => false, 'payment_provider' => 'duitku', 'notifications_whatsapp_enabled' => true, 'notifications_email_enabled' => true, 'duitku_mode' => 'sandbox', 'duitku_merchant_code' => 'D12345', 'duitku_api_key' => 'secret-key', 'mail_port' => 0, 'mail_from_name' => 'Panitia PMB PKU'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('settings', ['key' => 'pmb.registration_fee', 'value' => '375000']);
        $this->assertDatabaseHas('settings', ['key' => 'duitku.merchant_code', 'value' => 'D12345']);
        $this->assertDatabaseHas('settings', ['key' => 'mail.from_name', 'value' => 'Panitia PMB PKU']);
    }

    public function test_super_admin_can_send_a_test_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->post('/admin/settings/test-email', ['test_email_recipient' => 'panitia@example.test'])->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    public function test_score_labels_can_be_saved_and_are_used_on_the_score_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->put('/admin/settings', ['pmb_registration_fee' => 250000, 'registration_document_upload_disabled' => false, 'scores_label_1' => 'Tes Tulis', 'scores_label_2' => 'Bahasa Arab', 'scores_label_3' => 'Wawancara', 'scores_label_4' => 'Baca Kitab', 'payment_provider' => 'duitku', 'notifications_whatsapp_enabled' => true, 'notifications_email_enabled' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'scores.label_1', 'value' => 'Tes Tulis']);
        $this->actingAs($admin)->get('/admin/applicant-scores')->assertOk()->assertInertia(fn (Assert $page) => $page->where('scoreLabels', ['Tes Tulis', 'Bahasa Arab', 'Wawancara', 'Baca Kitab']));
    }
}
