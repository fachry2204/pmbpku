<?php

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationUploadRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_truncates_a_long_original_file_name_instead_of_failing(): void
    {
        Storage::fake('local');
        Queue::fake();
        Setting::create(['group' => 'registration', 'key' => 'registration.document_upload_disabled', 'value' => 'true', 'type' => 'boolean', 'is_encrypted' => false]);
        AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);

        $response = $this->post('/pendaftaran', [
            'submission_uuid' => '2f3f9489-3eaa-4c4e-a675-260892285b43',
            'full_name' => 'Nama Berkas Panjang',
            'birth_place' => 'Jakarta',
            'birth_date' => '2000-01-01',
            'address' => 'Alamat lengkap',
            'whatsapp' => '081234567891',
            'email' => 'long-file@example.test',
            'consent' => true,
            'diploma' => UploadedFile::fake()->image(str_repeat('a', 280).'.jpg'),
        ]);

        $response->assertRedirect();
        $document = Applicant::firstOrFail()->documents()->firstOrFail();
        $this->assertLessThanOrEqual(250, strlen($document->original_name));
    }

    public function test_registration_page_survives_an_unreadable_encrypted_setting(): void
    {
        Setting::create(['group' => 'payment', 'key' => 'payment.provider', 'value' => 'mayar_link', 'type' => 'string', 'is_encrypted' => false]);
        Setting::create(['group' => 'pmb', 'key' => 'pmb.registration_fee', 'value' => 'not-encrypted-data', 'type' => 'integer', 'is_encrypted' => true]);

        $this->get('/pendaftaran')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Register')
            ->where('registrationFee', 250000)
            ->where('maxTotalUploadBytes', fn ($value) => is_int($value) && $value > 0)
        );
    }

    public function test_deleted_registration_number_is_not_reused(): void
    {
        Storage::fake('local');
        Queue::fake();
        Setting::create(['group' => 'registration', 'key' => 'registration.document_upload_disabled', 'value' => 'true', 'type' => 'boolean', 'is_encrypted' => false]);
        $period = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);
        DB::table('registration_number_reservations')->insert([
            'admission_period_id' => $period->id,
            'registration_prefix' => 'PKU',
            'registration_year' => 2026,
            'sequence' => 1,
            'registration_number' => 'PKU-2026-000001',
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/pendaftaran', [
            'submission_uuid' => '6e15ec89-2666-4731-8cb9-b2a29a43b432',
            'full_name' => 'Nomor Baru',
            'birth_place' => 'Jakarta',
            'birth_date' => '2000-01-01',
            'address' => 'Alamat lengkap',
            'whatsapp' => '081234567892',
            'email' => 'reserved-number@example.test',
            'consent' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('applicants', ['registration_number' => 'PKU-2026-000002']);
    }
}
