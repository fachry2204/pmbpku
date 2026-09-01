<?php

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_private_ulid_document_and_audit_is_recorded(): void
    {
        Storage::fake('local');
        $period = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);
        $applicant = Applicant::create(['admission_period_id' => $period->id, 'registration_number' => 'PKU-2026-008888', 'submission_uuid' => fake()->uuid(), 'full_name' => 'Unduh Dokumen', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'whatsapp_normalized' => '6281234567890', 'whatsapp_display' => '081234567890', 'email' => 'download@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
        $path = 'applicants/'.$applicant->id.'/ijazah.pdf';
        Storage::disk('local')->put($path, 'isi-pdf');
        $document = $applicant->documents()->create(['type' => 'diploma', 'disk' => 'local', 'path' => $path, 'original_name' => 'Ijazah Peserta.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 7, 'sha256' => hash('sha256', 'isi-pdf')]);
        $admin = User::factory()->create(['role' => 'admin_pmb', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/documents/'.$document->id.'/download');

        $response->assertOk()->assertDownload('Ijazah Peserta.pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.download', 'auditable_id' => $document->id]);
    }

    public function test_admin_can_view_pass_photo_inline(): void
    {
        Storage::fake('local');
        $period = AdmissionPeriod::create(['name' => 'Foto', 'year' => 2027, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);
        $applicant = Applicant::create(['admission_period_id' => $period->id, 'registration_number' => 'PKU-2027-008889', 'submission_uuid' => fake()->uuid(), 'full_name' => 'Foto Peserta', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'whatsapp_normalized' => '6281234567891', 'whatsapp_display' => '081234567891', 'email' => 'photo@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
        $path = 'applicants/'.$applicant->id.'/foto.jpg';
        Storage::disk('local')->put($path, 'gambar');
        $document = $applicant->documents()->create(['type' => 'photo_4x6', 'disk' => 'local', 'path' => $path, 'original_name' => 'foto.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 6, 'sha256' => hash('sha256', 'gambar')]);
        $admin = User::factory()->create(['role' => 'admin_pmb', 'is_active' => true]);
        $this->actingAs($admin)->get('/admin/documents/'.$document->id.'/view')->assertOk()->assertHeader('content-type', 'image/jpeg');
    }
}
