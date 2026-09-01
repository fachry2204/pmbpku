<?php

namespace App\Observers;

use App\Actions\QueueApplicantNotification;
use App\Models\Applicant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ApplicantObserver
{
    public function updated(Applicant $applicant): void
    {
        $changes = [
            'payment_status' => 'payment_',
            'document_status' => 'document_',
            'selection_status' => 'selection_',
        ];

        foreach ($changes as $attribute => $prefix) {
            if (! $applicant->wasChanged($attribute)) continue;
            $value = (string) ($applicant->getChanges()[$attribute] ?? $applicant->getRawOriginal($attribute));
            $event = $prefix.$value;
            $occurrence = $applicant->updated_at?->format('YmdHis.u').'-'.Str::random(6);
            DB::afterCommit(function () use ($applicant, $event, $occurrence): void {
                try {
                    app(QueueApplicantNotification::class)->execute($applicant->fresh(), $event, '', $occurrence);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        }
    }
}
