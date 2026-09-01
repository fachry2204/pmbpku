<?php

namespace Tests\Feature;

use App\Jobs\SyncApplicantDocumentToDrive;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Services\RcloneStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GoogleDriveStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_document_is_moved_to_drive_after_verified_upload(): void
    {
        Storage::fake('local');
        Queue::fake();
        $applicant = $this->applicant();
        $localPath = $applicant->storageDirectory().'/dokumen.pdf';
        Storage::disk('local')->put($localPath, 'dokumen');
        $document = $applicant->documents()->create(['type' => 'diploma', 'disk' => 'local', 'path' => $localPath, 'original_name' => 'dokumen.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 7, 'sha256' => hash('sha256', 'dokumen')]);

        $drive = Mockery::mock(RcloneStorageService::class);
        $drive->shouldReceive('enabled')->once()->andReturnTrue();
        $drive->shouldReceive('destination')->once()->andReturn('PMB-PKU/PKU-2026-000001/diploma.pdf');
        $drive->shouldReceive('uploadLocal')->once()->with($localPath, 'PMB-PKU/PKU-2026-000001/diploma.pdf');

        (new SyncApplicantDocumentToDrive($document->id))->handle($drive);

        $this->assertSame('rclone', $document->fresh()->disk);
        Storage::disk('local')->assertMissing($localPath);
    }

    public function test_photo_is_copied_but_kept_locally(): void
    {
        Storage::fake('local');
        Queue::fake();
        $applicant = $this->applicant();
        $localPath = $applicant->storageDirectory().'/foto.jpg';
        Storage::disk('local')->put($localPath, 'foto');
        $document = $applicant->documents()->create(['type' => 'photo_4x6', 'disk' => 'local', 'path' => $localPath, 'original_name' => 'foto.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 4, 'sha256' => hash('sha256', 'foto')]);

        $drive = Mockery::mock(RcloneStorageService::class);
        $drive->shouldReceive('enabled')->once()->andReturnTrue();
        $drive->shouldReceive('destination')->once()->andReturn('PMB-PKU/PKU-2026-000001/photo.jpg');
        $drive->shouldReceive('uploadLocal')->once();

        (new SyncApplicantDocumentToDrive($document->id))->handle($drive);

        $this->assertSame('local', $document->fresh()->disk);
        Storage::disk('local')->assertExists($localPath);
    }

    private function applicant(): Applicant
    {
        $period = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);

        return Applicant::create(['admission_period_id' => $period->id, 'registration_number' => 'PKU-2026-000001', 'submission_uuid' => fake()->uuid(), 'full_name' => 'Pendaftar Drive', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'whatsapp_normalized' => '628111111111', 'whatsapp_display' => '08111111111', 'email' => 'drive@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
    }
}
