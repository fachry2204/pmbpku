<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'integer', 'exists:users,id'],
            'date' => ['nullable', 'date'],
        ]);

        $query = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->leftJoin('applicants as audited_applicant', function (JoinClause $join): void {
                $join->on('audited_applicant.id', '=', 'audit_logs.auditable_id')->where('audit_logs.auditable_type', Applicant::class);
            })
            ->leftJoin('applicant_documents as audited_document', function (JoinClause $join): void {
                $join->on('audited_document.id', '=', 'audit_logs.auditable_id')->where('audit_logs.auditable_type', ApplicantDocument::class);
            })
            ->leftJoin('applicants as document_applicant', 'document_applicant.id', '=', 'audited_document.applicant_id')
            ->select([
                'audit_logs.id', 'audit_logs.action', 'audit_logs.before_json', 'audit_logs.after_json', 'audit_logs.created_at', 'users.name as user_name',
                'audited_applicant.registration_number as applicant_number', 'audited_applicant.full_name as applicant_name',
                'audited_document.type as document_type', 'audited_document.original_name as document_name',
                'document_applicant.registration_number as document_applicant_number', 'document_applicant.full_name as document_applicant_name',
            ]);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('users.name', 'like', "%{$search}%")->orWhere('audited_applicant.registration_number', 'like', "%{$search}%")
                    ->orWhere('audited_applicant.full_name', 'like', "%{$search}%")->orWhere('document_applicant.registration_number', 'like', "%{$search}%")
                    ->orWhere('document_applicant.full_name', 'like', "%{$search}%")->orWhere('audited_document.original_name', 'like', "%{$search}%");
            });
        }
        foreach (['action' => 'audit_logs.action', 'actor' => 'audit_logs.user_id'] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) $query->where($column, $filters[$filter]);
        }
        if (filled($filters['date'] ?? null)) $query->whereDate('audit_logs.created_at', $filters['date']);

        $logs = $query->latest('audit_logs.created_at')->paginate(25)->withQueryString();
        $logs->through(fn (object $log): array => $this->formatLog($log));

        return Inertia::render('Admin/Logs/Audit', [
            'logs' => $logs,
            'filters' => ['search' => $filters['search'] ?? '', 'action' => $filters['action'] ?? '', 'actor' => (string) ($filters['actor'] ?? ''), 'date' => $filters['date'] ?? ''],
            'actions' => DB::table('audit_logs')->distinct()->orderBy('action')->pluck('action')->map(fn (string $action): array => ['value' => $action, 'label' => $this->actionLabel($action)])->values(),
            'actors' => DB::table('audit_logs')->join('users', 'users.id', '=', 'audit_logs.user_id')->select('users.id', 'users.name')->distinct()->orderBy('users.name')->get(),
        ]);
    }

    private function formatLog(object $log): array
    {
        $date = Carbon::parse($log->created_at)->locale('id');
        $isDocument = filled($log->document_name) || filled($log->document_type);
        $number = $isDocument ? $log->document_applicant_number : $log->applicant_number;
        $name = $isDocument ? $log->document_applicant_name : $log->applicant_name;
        return [
            'id' => $log->id, 'action_label' => $this->actionLabel($log->action), 'tone' => $this->actionTone($log->action),
            'actor' => $log->user_name ?: 'Sistem', 'actor_initials' => $this->initials($log->user_name ?: 'Sistem'),
            'subject' => ['registration_number' => $number, 'applicant_name' => $name, 'document' => $isDocument ? ($log->document_name ?: $this->documentLabel($log->document_type)) : null],
            'summary' => $this->summary($log), 'changes' => $this->changes($log->before_json, $log->after_json),
            'created_at' => $date->translatedFormat('d M Y, H:i').' WIB', 'relative_time' => $date->diffForHumans(),
        ];
    }

    private function actionLabel(string $action): string
    {
        return ['document.download' => 'Dokumen diunduh', 'applicant.document.admin_upload' => 'Dokumen pendaftar diperbarui', 'applicant.status.manual_update' => 'Status pendaftar diperbarui', 'applicant.notification.manual_resend' => 'Notifikasi status dikirim ulang', 'applicant.selection.bulk_schedule' => 'Jadwal seleksi ditetapkan', 'applicant.selection.attendance_confirmed' => 'Kehadiran seleksi dikonfirmasi'][$action] ?? 'Aktivitas sistem';
    }
    private function actionTone(string $action): string { return str_contains($action, 'download') ? 'blue' : (str_contains($action, 'notification') ? 'violet' : (str_contains($action, 'status') ? 'amber' : 'emerald')); }
    private function summary(object $log): string
    {
        $after = $this->decode($log->after_json);
        return match ($log->action) {
            'document.download' => 'Dokumen pendaftar diakses secara aman.',
            'applicant.document.admin_upload' => 'Dokumen '.$this->documentLabel($after['type'] ?? null).' disimpan sebagai versi '.($after['version'] ?? 1).'.',
            'applicant.notification.manual_resend' => 'Notifikasi sesuai status pendaftar dikirim melalui '.($after['channels_processed'] ?? 0).' kanal aktif.',
            'applicant.selection.bulk_schedule' => 'Pendaftar dimasukkan ke jadwal seleksi.',
            'applicant.selection.attendance_confirmed' => 'Kehadiran pendaftar pada sesi seleksi telah dicatat.',
            default => 'Perubahan data dilakukan melalui panel administrasi.',
        };
    }
    private function changes(?string $beforeJson, ?string $afterJson): array
    {
        $before = $this->decode($beforeJson); $after = $this->decode($afterJson); $changes = [];
        foreach (['payment_status' => 'Status pembayaran', 'document_status' => 'Status berkas', 'selection_status' => 'Status seleksi'] as $key => $label) {
            if (array_key_exists($key, $after)) $changes[] = ['label' => $label, 'before' => $this->statusLabel($before[$key] ?? null), 'after' => $this->statusLabel($after[$key])];
        }
        if (filled($after['reason'] ?? null)) $changes[] = ['label' => 'Catatan admin', 'before' => null, 'after' => $after['reason']];
        return $changes;
    }
    private function decode(?string $json): array { return is_string($json) ? (json_decode($json, true) ?: []) : []; }
    private function statusLabel(?string $status): string { return ['unpaid' => 'Belum Bayar', 'pending' => 'Menunggu Verifikasi', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'failed' => 'Gagal', 'refunded' => 'Dikembalikan', 'pending_review' => 'Menunggu Review', 'complete' => 'Lengkap', 'incomplete' => 'Belum Lengkap', 'revision_requested' => 'Perlu Perbaikan', 'revision_submitted' => 'Perbaikan Dikirim', 'not_scheduled' => 'Belum Dijadwalkan', 'scheduled' => 'Terjadwal', 'attending_test' => 'Mengikuti Seleksi', 'passed' => 'Lulus Seleksi', 'not_passed' => 'Tidak Lulus', 'withdrawn' => 'Mengundurkan Diri'][$status] ?? ($status ?: 'Belum ada data'); }
    private function documentLabel(?string $type): string { return ['recommendation_letter' => 'Surat Rekomendasi', 'diploma' => 'Ijazah', 'photo' => 'Foto 4×6', 'ktp' => 'KTP', 'pddikti' => 'Screenshot PDDIKTI / Penyetaraan'][$type] ?? 'pendaftar'; }
    private function initials(string $name): string { return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->join(''); }
}
