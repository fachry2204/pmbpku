<?php

namespace App\Actions;

use App\Jobs\SendApplicantNotification;
use App\Models\Applicant;
use App\Models\NotificationLog;
use App\Services\NotificationTemplateService;
use App\Services\SettingsService;
use Illuminate\Support\Str;

final class QueueApplicantNotification
{
    public function __construct(
        private NotificationTemplateService $templates,
        private SettingsService $settings
    ) {}

    public function execute(Applicant $applicant, string $event, string $fallback = '', ?string $occurrence = null): void
    {
        $message = $this->templates->render($event, $applicant, $fallback);
        $occurrence ??= $event;

        foreach (['email', 'whatsapp'] as $channel) {
            if (! $this->settings->get('notifications.'.$channel.'_enabled', true)) {
                continue;
            }

            $recipient = $channel === 'email'
                ? preg_replace('/(^.).*(@.*$)/', '$1***$2', $applicant->email)
                : substr($applicant->whatsapp_normalized, 0, 4).'****'.substr($applicant->whatsapp_normalized, -3);
            $key = Str::limit("{$event}:{$channel}:{$applicant->id}:{$occurrence}", 250, '');
            $log = NotificationLog::firstOrCreate(['unique_key' => $key], [
                'applicant_id' => $applicant->id,
                'channel' => $channel,
                'event_type' => $event,
                'recipient_masked' => $recipient,
                'status' => 'queued',
                'attempts' => 0,
            ]);

            // Diproses langsung agar notifikasi tetap terkirim di Plesk tanpa queue worker.
            if ($log->wasRecentlyCreated) {
                SendApplicantNotification::dispatchSync($log->id, $message);
            }
        }
    }
}
