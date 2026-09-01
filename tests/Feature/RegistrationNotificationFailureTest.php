<?php

namespace Tests\Feature;

use App\Models\{AdmissionPeriod, Applicant, Setting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationNotificationFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_failure_does_not_break_a_saved_registration(): void
    {
        Storage::fake('local');
        AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);
        Setting::create(['group'=>'notifications','key'=>'notifications.registration_created','value'=>'invalid-encrypted-value','type'=>'string','is_encrypted'=>true]);
        $files = [];
        foreach (['recommendation_letter','diploma','photo_4x6','identity_card','pddikti_screenshot'] as $type) $files[$type] = UploadedFile::fake()->image($type.'.jpg');

        $response = $this->post('/pendaftaran', [...$files,
            'submission_uuid'=>'81c71465-4055-4ca0-a69d-d5cf299c525d','payment_method'=>'BRIVA',
            'full_name'=>'Pendaftar Tetap Tersimpan','birth_place'=>'Jakarta','birth_date'=>'2000-01-01',
            'address'=>'Alamat lengkap','whatsapp'=>'081234567899','email'=>'safe@example.test','consent'=>true,
        ]);

        $applicant = Applicant::firstOrFail();
        $response->assertRedirect(route('payment.show', ['registrationNumber'=>$applicant->registration_number,'method'=>'BRIVA','registered'=>1]));
    }

    public function test_duplicate_email_returns_validation_error_instead_of_server_error(): void
    {
        Storage::fake('local');
        $period = AdmissionPeriod::create(['name'=>'Aktif','year'=>2026,'registration_prefix'=>'PKU','starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'registration_fee'=>250000,'is_active'=>true]);
        Applicant::create(['admission_period_id'=>$period->id,'registration_number'=>'PKU-2026-000001','submission_uuid'=>fake()->uuid(),'full_name'=>'Pendaftar Lama','birth_place'=>'Jakarta','birth_date'=>'2000-01-01','address'=>'Alamat','whatsapp_normalized'=>'628111111111','whatsapp_display'=>'08111111111','email'=>'duplicate@example.test','payment_status'=>'unpaid','document_status'=>'pending_review','selection_status'=>'not_scheduled','consented_at'=>now(),'submitted_at'=>now()]);
        $files = [];
        foreach (['recommendation_letter','diploma','photo_4x6','identity_card','pddikti_screenshot'] as $type) $files[$type] = UploadedFile::fake()->image($type.'.jpg');

        $this->from('/pendaftaran')->post('/pendaftaran', [...$files,
            'submission_uuid'=>'63c1ae11-6d20-457d-9e12-9d843dc5ce63','payment_method'=>'BRIVA',
            'full_name'=>'Pendaftar Baru','birth_place'=>'Jakarta','birth_date'=>'2000-01-01','address'=>'Alamat lengkap',
            'whatsapp'=>'081222222222','email'=>'duplicate@example.test','consent'=>true,
        ])->assertRedirect('/pendaftaran')->assertSessionHasErrors('email');

        $this->assertDatabaseCount('applicants', 1);
    }
}
