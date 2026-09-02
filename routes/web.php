<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\DocumentRevisionController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\Public\RegistrationController;
use App\Http\Controllers\Public\StatusLookupController;
use App\Http\Controllers\SelectionCardController;
use App\Http\Controllers\Webhooks\DuitkuWebhookController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use App\Http\Controllers\Webhooks\TripayWebhookController;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (SettingsService $s) {
    return Inertia::render('Public/Home', ['content' => ['hero_title' => $s->get('landing.hero_title', 'Penerimaan Mahasiswa Baru Pendidikan Kader Ulama'), 'hero_description' => $s->get('landing.hero_description', 'Mempersiapkan kader berilmu, berakhlak, dan siap mengabdi kepada umat.'), 'requirements' => $s->get('landing.requirements', 'Ijazah, identitas, rekomendasi, foto, dan dokumen PDDIKTI.'), 'contact' => $s->get('landing.contact', 'Hubungi panitia PMB untuk informasi lebih lanjut.'), 'faq' => $s->get('landing.faq', [])], 'registrationYear' => (int) $s->get('pmb.registration_year', now()->year)]);
});
Route::get('/syarat-dan-ketentuan', function () {
    return Inertia::render('Public/Terms', [
        'content' => file_get_contents(resource_path('content/terms-and-conditions.txt')),
    ]);
})->name('terms');
Route::get('/health', HealthController::class)->middleware('throttle:30,1')->name('health');
Route::get('/pendaftaran', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('registration.store');
Route::get('/pendaftaran/{registrationNumber}/berhasil', [RegistrationController::class, 'success'])->name('registration.success');
Route::get('/pembayaran/{registrationNumber}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pembayaran/{registrationNumber}/gateway', [PaymentController::class, 'create'])->middleware('throttle:10,1')->name('payment.create');
Route::get('/cek-status', [StatusLookupController::class, 'index'])->name('status.index');
Route::post('/cek-status', [StatusLookupController::class, 'lookup'])->middleware('throttle:10,10')->name('status.lookup');
Route::get('/cek-status/email/{applicant}', [StatusLookupController::class, 'emailAccess'])->middleware(['signed', 'throttle:20,1'])->name('status.email');
Route::get('/cek-status/detail', [StatusLookupController::class, 'show'])->name('status.show');
Route::get('/cek-status/foto', [StatusLookupController::class, 'photo'])->name('status.photo');
Route::get('/cek-status/kartu-seleksi', [SelectionCardController::class, 'publicDownload'])->name('status.selection-card');
Route::get('/cek-status/bukti-registrasi', [SelectionCardController::class, 'publicRegistrationDownload'])->name('status.registration-proof');
Route::post('/cek-status/{applicant}/documents/{type}/revision', DocumentRevisionController::class)->middleware('throttle:5,10')->name('status.document-revision');
Route::post('/webhooks/duitku', DuitkuWebhookController::class)->middleware('throttle:120,1')->name('webhooks.duitku');
Route::post('/webhooks/tripay', TripayWebhookController::class)->middleware('throttle:120,1')->name('webhooks.tripay');
Route::post('/webhooks/midtrans', MidtransWebhookController::class)->middleware('throttle:120,1')->name('webhooks.midtrans');

Route::middleware(['auth', 'active.admin:admin_pmb'])->group(function () {
    Route::get('/absen', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/absen', [AttendanceController::class, 'store'])->middleware('throttle:60,1')->name('attendance.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
