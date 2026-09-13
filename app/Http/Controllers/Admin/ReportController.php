<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function applicants(Request $request): StreamedResponse
    {
        $query = Applicant::query();
        foreach (['payment_status', 'document_status', 'selection_status', 'admission_period_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nomor Pendaftaran', 'Nama', 'Email', 'WhatsApp', 'Pembayaran', 'Berkas', 'Seleksi', 'Tanggal Daftar']);
            $query->orderBy('registration_number')->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $applicant) {
                    fputcsv($out, [$applicant->registration_number, $applicant->full_name, $applicant->email, $applicant->whatsapp_normalized, $applicant->payment_status->value, $applicant->document_status->value, $applicant->selection_status->value, $applicant->submitted_at]);
                }
            });
            fclose($out);
        }, 'laporan-pendaftar-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function paidApplicantsAttendance(Request $request): StreamedResponse
    {
        $query = Applicant::query()->where('payment_status', PaymentStatus::Paid->value);
        $this->applyApplicantListFilters($query, $request);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            // Excel menggunakan penanda ini agar nama berbahasa Indonesia tidak rusak saat dibuka.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No.', 'Nomor Pendaftaran', 'Nama Peserta', 'Nomor WhatsApp', 'Email', 'Status Pembayaran', 'Kehadiran', 'Tanda Tangan', 'Catatan']);
            $number = 0;
            $query->orderBy('registration_number')->chunk(500, function ($rows) use ($out, &$number): void {
                foreach ($rows as $applicant) {
                    ++$number;
                    fputcsv($out, [$number, $applicant->registration_number, $applicant->full_name, $applicant->whatsapp_display, $applicant->email, 'Sudah Bayar', '', '', '']);
                }
            });
            fclose($out);
        }, 'absensi-pendaftar-sudah-bayar-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyApplicantListFilters(Builder $query, Request $request): void
    {
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn (Builder $applicants) => $applicants
                ->where('registration_number', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%"));
        }
        if ($request->filled('registration_year')) {
            $query->whereHas('admissionPeriod', fn (Builder $period) => $period->where('year', $request->integer('registration_year')));
        }
        foreach (['document_status', 'selection_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        match ($request->string('registration_status')->toString()) {
            'selection_passed' => $query->where('selection_status', SelectionStatus::Passed->value),
            'selection_stage' => $query->where('selection_status', '!=', SelectionStatus::NotScheduled->value)->where('selection_status', '!=', SelectionStatus::Passed->value),
            'documents_complete' => $query->where('selection_status', SelectionStatus::NotScheduled->value)->where('document_status', DocumentStatus::Complete->value),
            'paid' => $query->where('selection_status', SelectionStatus::NotScheduled->value)->where('document_status', '!=', DocumentStatus::Complete->value),
            'not_paid' => $query->where('selection_status', SelectionStatus::NotScheduled->value)->where('document_status', '!=', DocumentStatus::Complete->value),
            default => null,
        };
    }
}
