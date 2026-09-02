<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendApplicantNotification;
use App\Models\NotificationLog;
use App\Services\NotificationTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NotificationLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NotificationLog::with('applicant:id,registration_number,full_name')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('applicant', fn ($applicant) => $applicant
                ->where('registration_number', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%"));
        }

        return Inertia::render('Admin/Logs/Notifications', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'channel']),
            'summary' => [
                'sent' => NotificationLog::where('status', 'sent')->count(),
                'queued' => NotificationLog::where('status', 'queued')->count(),
                'failed' => NotificationLog::whereIn('status', ['failed', 'skipped'])->count(),
            ],
        ]);
    }

    public function retry(NotificationLog $notificationLog, NotificationTemplateService $templates): RedirectResponse
    {
        abort_unless(in_array($notificationLog->status, ['queued', 'failed', 'skipped'], true), 422);
        abort_unless($notificationLog->applicant, 422, 'Data pendaftar sudah tidak tersedia.');
        $notificationLog->update(['status' => 'queued', 'last_error' => null]);

        try {
            SendApplicantNotification::dispatchSync(
                $notificationLog->id,
                $templates->render($notificationLog->event_type, $notificationLog->applicant)
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pesan belum berhasil dikirim. Periksa keterangan error pada tabel.');
        }

        return back()->with('success', 'Pesan berhasil diproses ulang.');
    }

    public function processPending(NotificationTemplateService $templates): RedirectResponse
    {
        $logs = NotificationLog::with('applicant')->whereIn('status', ['queued', 'failed'])->oldest()->limit(50)->get();
        $sent = 0;

        foreach ($logs as $log) {
            if (! $log->applicant) {
                $log->update(['status' => 'skipped', 'last_error' => 'Data pendaftar sudah tidak tersedia.']);

                continue;
            }
            $log->update(['status' => 'queued', 'last_error' => null]);
            try {
                SendApplicantNotification::dispatchSync($log->id, $templates->render($log->event_type, $log->applicant));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with(
            $sent === $logs->count() ? 'success' : 'error',
            "{$sent} dari {$logs->count()} pesan berhasil diproses."
        );
    }
}
