<?php

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusLookupTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $period = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);

        return Applicant::create(['admission_period_id' => $period->id, 'registration_number' => 'PKU-2026-000009', 'submission_uuid' => '9a2dad61-e2ad-47cf-a58a-67c829c23e35', 'full_name' => 'Nama Fiktif', 'birth_place' => 'Bogor', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'whatsapp_normalized' => '6281234567890', 'whatsapp_display' => '081234567890', 'email' => 'status@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
    }

    public function test_detail_is_not_available_without_lookup(): void
    {
        $this->get('/cek-status/detail')->assertForbidden();
    }

    public function test_status_can_be_found_by_email_without_otp(): void
    {
        $applicant = $this->applicant();
        $this->post('/cek-status', ['identifier' => strtoupper($applicant->email)])
            ->assertRedirect(route('status.show'))
            ->assertSessionHas('status_applicant_id', $applicant->id);
    }

    public function test_status_can_be_found_by_phone_without_otp(): void
    {
        $applicant = $this->applicant();
        $this->post('/cek-status', ['identifier' => '0812 3456 7890'])
            ->assertRedirect(route('status.show'))
            ->assertSessionHas('status_applicant_id', $applicant->id);
    }

    public function test_status_can_be_found_by_registration_number(): void
    {
        $applicant = $this->applicant();

        $this->post('/cek-status', ['identifier' => '  pku-2026-000009  '])
            ->assertRedirect(route('status.show'))
            ->assertSessionHas('status_applicant_id', $applicant->id);
    }

    public function test_unknown_identity_returns_validation_error(): void
    {
        $this->applicant();
        $this->post('/cek-status', ['identifier' => 'wrong@example.test'])
            ->assertSessionHasErrors('identifier');
    }
}
