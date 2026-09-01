<?php

namespace App\Observers;

use App\Jobs\SyncApplicantDocumentToDrive;
use App\Models\ApplicantDocument;
use App\Services\SettingsService;

class ApplicantDocumentObserver
{
    public function __construct(private readonly SettingsService $settings) {}

    public function created(ApplicantDocument $document): void
    {
        if ($this->settings->get('storage.google_drive_enabled', false)) {
            SyncApplicantDocumentToDrive::dispatch($document->id)->afterCommit();
        }
    }
}
