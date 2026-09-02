<?php

namespace Tests\Feature;

use App\Jobs\SendApplicantNotification;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplicantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): Applicant
    {
        $p = AdmissionPeriod::create(['name' => 'Aktif', 'year' => 2026, 'registration_prefix' => 'PKU', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'registration_fee' => 250000, 'is_active' => true]);

        return Applicant::create(['admission_period_id' => $p->id, 'registration_number' => 'PKU-2026-123456', 'submission_uuid' => fake()->uuid(), 'full_name' => 'Nama Lama', 'birth_place' => 'Jakarta', 'birth_date' => '2000-01-01', 'address' => 'Alamat Lama', 'whatsapp_normalized' => '628111111111', 'whatsapp_display' => '08111111111', 'email' => 'lama@example.test', 'payment_status' => 'unpaid', 'document_status' => 'pending_review', 'selection_status' => 'not_scheduled', 'consented_at' => now(), 'submitted_at' => now()]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin_pmb', 'is_active' => true]);
    }

    public function test_admin_can_edit_applicant_on_dedicated_page(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->admin())->get("/admin/applicants/{$a->id}/edit")->assertOk();
        $this->actingAs($this->admin())->put("/admin/applicants/{$a->id}", ['full_name' => 'Nama Baru', 'birth_place' => 'Bandung', 'birth_date' => '2001-02-03', 'address' => 'Alamat Baru', 'email' => 'baru@example.test', 'whatsapp' => '081222222222'])->assertRedirect("/admin/applicants/{$a->id}");
        $this->assertDatabaseHas('applicants', ['id' => $a->id, 'full_name' => 'Nama Baru', 'email' => 'baru@example.test']);
    }

    public function test_applicant_list_can_be_filtered_by_registration_year(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->admin())->get('/admin/applicants?registration_year=2026')->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('applicants.data', 1)
                ->where('applicants.data.0.id', $applicant->id)
                ->where('filters.registration_year', '2026')
                ->where('registrationYears.0', 2026)
        );

        $this->actingAs($this->admin())->get('/admin/applicants?registration_year=2025')->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('applicants.data', 0)
        );
    }

    public function test_delete_removes_applicant_and_private_files(): void
    {
        Storage::fake('local');
        $a = $this->applicant();
        $path = $a->storageDirectory().'/test.pdf';
        Storage::disk('local')->put($path, 'file');
        $a->documents()->create(['type' => 'diploma', 'disk' => 'local', 'path' => $path, 'original_name' => 'test.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 4, 'sha256' => hash('sha256', 'file')]);
        $this->actingAs($this->admin())->delete("/admin/applicants/{$a->id}")->assertRedirect('/admin/applicants');
        $this->assertDatabaseMissing('applicants', ['id' => $a->id]);
        Storage::disk('local')->assertMissing($a->storageDirectory());
    }

    public function test_admin_can_update_status_and_the_change_is_recorded(): void
    {
        Queue::fake();
        $applicant = $this->applicant();
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/applicants/{$applicant->id}/status", [
            'dimension' => 'selection',
            'status' => 'passed',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('passed', $applicant->fresh()->selection_status->value);
        $this->assertDatabaseHas('status_histories', [
            'applicant_id' => $applicant->id,
            'dimension' => 'selection',
            'from_status' => 'not_scheduled',
            'to_status' => 'passed',
            'changed_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'applicant.status.manual_update', 'auditable_id' => $applicant->id]);
    }

    public function test_manual_payment_status_requires_a_reason(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->admin())->patch("/admin/applicants/{$applicant->id}/status", [
            'dimension' => 'payment',
            'status' => 'paid',
            'reason' => '',
        ])->assertSessionHasErrors('reason');

        $this->assertSame('unpaid', $applicant->fresh()->payment_status->value);
    }

    public function test_scheduled_selection_requires_date_and_time(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($this->admin())->patch("/admin/applicants/{$applicant->id}/status", [
            'dimension' => 'selection',
            'status' => 'scheduled',
        ])->assertSessionHasErrors(['selection_date', 'selection_time']);

        $this->assertSame('not_scheduled', $applicant->fresh()->selection_status->value);
    }

    public function test_scheduled_selection_saves_date_and_time_as_a_test_session(): void
    {
        Queue::fake();
        $applicant = $this->applicant();

        $this->actingAs($this->admin())->patch("/admin/applicants/{$applicant->id}/status", [
            'dimension' => 'selection',
            'status' => 'scheduled',
            'selection_date' => now()->addDay()->format('Y-m-d'),
            'selection_time' => '09:30',
        ])->assertSessionHasNoErrors();

        $this->assertSame('scheduled', $applicant->fresh()->selection_status->value);
        $this->assertDatabaseHas('test_sessions', [
            'admission_period_id' => $applicant->admission_period_id,
            'name' => 'Seleksi '.$applicant->registration_number,
        ]);
        $this->assertDatabaseHas('applicant_test_sessions', [
            'applicant_id' => $applicant->id,
            'attendance_status' => 'assigned',
        ]);
    }

    public function test_admin_can_bulk_schedule_selected_applicants(): void
    {
        Queue::fake();
        app(SettingsService::class)->put(
            'notifications',
            'notifications.selection_scheduled',
            'Pendaftaran {registration_number} telah masuk jadwal seleksi. Silakan cek status untuk informasi selengkapnya.'
        );
        $first = $this->applicant();
        $second = $first->replicate(['registration_number', 'submission_uuid', 'email', 'whatsapp_normalized']);
        $second->fill([
            'registration_number' => 'PKU-2026-654321',
            'submission_uuid' => fake()->uuid(),
            'full_name' => 'Nama Kedua',
            'email' => 'kedua@example.test',
            'whatsapp_normalized' => '628122222222',
        ])->save();

        $this->actingAs($this->admin())->post('/admin/applicants/bulk-schedule', [
            'applicant_ids' => [$first->id, $second->id],
            'selection_date' => now()->addDays(2)->format('Y-m-d'),
            'selection_time' => '13:30',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('scheduled', $first->fresh()->selection_status->value);
        $this->assertSame('scheduled', $second->fresh()->selection_status->value);
        $this->assertDatabaseHas('applicant_test_sessions', ['applicant_id' => $first->id, 'attendance_status' => 'assigned']);
        $this->assertDatabaseHas('applicant_test_sessions', ['applicant_id' => $second->id, 'attendance_status' => 'assigned']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'applicant.selection.bulk_schedule', 'auditable_id' => $first->id]);
        Queue::assertPushed(SendApplicantNotification::class, fn (SendApplicantNotification $job) => str_contains($job->message, 'Tanggal:') && str_contains($job->message, 'Waktu: 13:30 WIB'));
    }

    public function test_manual_payment_status_saves_the_required_reason(): void
    {
        Queue::fake();
        $applicant = $this->applicant();

        $this->actingAs($this->admin())->patch("/admin/applicants/{$applicant->id}/status", [
            'dimension' => 'payment',
            'status' => 'paid',
            'reason' => 'Transfer diverifikasi langsung oleh bagian keuangan.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('paid', $applicant->fresh()->payment_status->value);
        $this->assertDatabaseHas('status_histories', [
            'applicant_id' => $applicant->id,
            'dimension' => 'payment',
            'note' => 'Transfer diverifikasi langsung oleh bagian keuangan.',
        ]);
    }

    public function test_registration_status_follows_the_five_requested_stages(): void
    {
        Queue::fake();
        $applicant = $this->applicant();
        $this->assertSame('Belum Bayar', $applicant->registration_status['label']);

        $applicant->update(['payment_status' => 'paid']);
        $this->assertSame('Sudah Bayar', $applicant->fresh()->registration_status['label']);

        $applicant->update(['document_status' => 'complete']);
        $this->assertSame('Berkas Lengkap', $applicant->fresh()->registration_status['label']);

        $applicant->update(['selection_status' => 'scheduled']);
        $this->assertSame('Tahap Seleksi', $applicant->fresh()->registration_status['label']);

        $applicant->update(['selection_status' => 'passed']);
        $this->assertSame('Lulus Seleksi', $applicant->fresh()->registration_status['label']);
    }
}
