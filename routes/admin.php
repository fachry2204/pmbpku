<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApplicantScoreController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentReviewController;
use App\Http\Controllers\Admin\LandingContentController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PrivateDocumentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\SelectionCardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth', 'active.admin:admin_pmb,finance,reviewer,viewer'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/applicants', [ApplicantController::class, 'index'])->name('admin.applicants.index');
    Route::get('/applicants/{applicant}', [ApplicantController::class, 'show'])->name('admin.applicants.show');
    Route::get('/applicants/{applicant}/download', [ApplicantController::class, 'download'])->name('admin.applicants.download');
    Route::get('/applicants/{applicant}/selection-card', [SelectionCardController::class, 'adminDownload'])->name('admin.applicants.selection-card');
    Route::patch('/applicants/{applicant}/status', [ApplicantController::class, 'updateStatus'])->name('admin.applicants.status');
    Route::get('/documents/{document}/download', PrivateDocumentController::class)->name('admin.documents.download')->middleware('active.admin:admin_pmb,reviewer');
    Route::patch('/applicants/{applicant}/documents/{document}', [DocumentReviewController::class, 'update'])->name('admin.documents.review')->middleware('active.admin:admin_pmb,reviewer');
    Route::middleware('active.admin:admin_pmb')->group(function () {
        Route::post('/applicants/bulk-schedule', [ApplicantController::class, 'bulkSchedule'])->name('admin.applicants.bulk-schedule');
        Route::get('/applicants/{applicant}/edit', [ApplicantController::class, 'edit'])->name('admin.applicants.edit');
        Route::put('/applicants/{applicant}', [ApplicantController::class, 'update'])->name('admin.applicants.update');
        Route::post('/applicants/{applicant}/documents', [ApplicantController::class, 'uploadDocument'])->name('admin.applicants.documents.upload');
        Route::delete('/applicants/{applicant}', [ApplicantController::class, 'destroy'])->name('admin.applicants.destroy');
        Route::get('/applicant-scores', [ApplicantScoreController::class, 'index'])->name('admin.applicant-scores.index');
        Route::patch('/applicant-scores/{applicant}', [ApplicantScoreController::class, 'update'])->name('admin.applicant-scores.update');
        Route::get('/attendance', [AttendanceController::class, 'adminIndex'])->name('admin.attendance.index');
        Route::get('/attendance/pdf', [AttendanceController::class, 'downloadPdf'])->name('admin.attendance.pdf');
    });
});
Route::get('admin/documents/{document}/view', [PrivateDocumentController::class, 'inline'])->middleware(['auth', 'active.admin:admin_pmb,reviewer'])->name('admin.documents.view');
Route::prefix('admin/users')->middleware(['auth', 'active.admin:super_admin'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
    Route::patch('/{user}', [UserController::class, 'update'])->name('admin.users.update');
});
Route::get('admin/reports/applicants.csv', [ReportController::class, 'applicants'])->middleware(['auth', 'active.admin:admin_pmb,finance,viewer'])->name('admin.reports.applicants');
Route::prefix('admin/settings')->middleware(['auth', 'active.admin:super_admin'])->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/mayar-key', [SettingsController::class, 'uploadMayarKey'])->middleware('throttle:10,1')->name('admin.settings.mayar-key');
    Route::post('/test-email', [SettingsController::class, 'testEmail'])->middleware('throttle:5,1')->name('admin.settings.test-email');
    Route::post('/test-drive', [SettingsController::class, 'testDrive'])->middleware('throttle:5,1')->name('admin.settings.test-drive');
});
Route::prefix('admin/payments')->middleware(['auth', 'active.admin:finance'])->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::patch('/{payment}/verify', [PaymentController::class, 'verify'])->name('admin.payments.verify');
});
Route::prefix('admin/landing')->middleware(['auth', 'active.admin:admin_pmb'])->group(function () {
    Route::get('/', [LandingContentController::class, 'edit'])->name('admin.landing.edit');
    Route::put('/', [LandingContentController::class, 'update'])->name('admin.landing.update');
});
Route::middleware(['auth', 'active.admin:admin_pmb,finance'])->group(function () {
    Route::get('admin/notification-logs', [NotificationLogController::class, 'index'])->name('admin.notification-logs.index');
    Route::post('admin/notification-logs/process-pending', [NotificationLogController::class, 'processPending'])->name('admin.notification-logs.process-pending');
    Route::post('admin/notification-logs/{notificationLog}/retry', [NotificationLogController::class, 'retry'])->name('admin.notification-logs.retry');
});
Route::get('admin/audit-logs', AuditLogController::class)->middleware(['auth', 'active.admin:super_admin'])->name('admin.audit-logs.index');
Route::prefix('admin/notification-templates')->middleware(['auth', 'active.admin:super_admin'])->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'edit'])->name('admin.notification-templates.edit');
    Route::put('/', [NotificationTemplateController::class, 'update'])->name('admin.notification-templates.update');
});
