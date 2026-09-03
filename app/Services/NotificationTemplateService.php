<?php

namespace App\Services;

use App\Models\Applicant;

final class NotificationTemplateService
{
    private const DEFAULTS = [
        'registration_created' => "Assalamu'alaikum {full_name},\n\nPendaftaran Anda berhasil diterima dengan nomor {registration_number}. Simpan nomor tersebut untuk mengecek status pendaftaran.\n\nStatus pembayaran saat ini: {payment_status_label}. Silakan selesaikan pembayaran sesuai petunjuk pada halaman pendaftaran.",
        'payment_unpaid' => "Assalamu'alaikum {full_name},\n\nPembayaran untuk pendaftaran {registration_number} belum dilakukan. Silakan lanjutkan pembayaran sebelum batas waktu agar proses pendaftaran dapat diteruskan.",
        'payment_pending' => "Assalamu'alaikum {full_name},\n\nPembayaran pendaftaran {registration_number} sedang menunggu konfirmasi. Kami akan memberitahukan kembali setelah pembayaran berhasil diverifikasi.",
        'payment_paid' => 'Alhamdulillah, pembayaran pendaftaran {registration_number} atas nama {full_name} telah berhasil dan terverifikasi. Anda dapat melanjutkan proses berikutnya.',
        'payment_failed' => "Assalamu'alaikum {full_name},\n\nPembayaran pendaftaran {registration_number} tidak berhasil atau dibatalkan. Silakan lakukan pembayaran kembali atau hubungi panitia jika dana telah terpotong.",
        'payment_expired' => 'Masa berlaku pembayaran pendaftaran {registration_number} telah berakhir. Silakan membuat transaksi pembayaran baru.',
        'payment_refunded' => 'Dana pembayaran pendaftaran {registration_number} telah dikembalikan. Hubungi panitia jika Anda membutuhkan informasi lebih lanjut.',
        'document_pending_review' => 'Dokumen pendaftaran {registration_number} telah diterima dan sedang diperiksa oleh panitia.',
        'document_revision_submitted' => 'Dokumen perbaikan untuk {registration_number} telah diterima dan akan diperiksa kembali.',
        'document_incomplete' => 'Dokumen pendaftaran {registration_number} memerlukan perbaikan. Silakan cek status pendaftaran untuk melihat catatan panitia dan unggah dokumen yang sesuai.',
        'document_complete' => 'Alhamdulillah, seluruh dokumen pendaftaran {registration_number} telah dinyatakan lengkap.',
        'selection_not_scheduled' => 'Status seleksi {registration_number} diperbarui: belum dijadwalkan. Pantau informasi berikutnya melalui halaman cek status.',
        'selection_scheduled' => "Pendaftaran {registration_number} telah masuk jadwal seleksi.\n\nJadwal Seleksi:\nTanggal: {selection_date}\nWaktu: {selection_time} WIB\nLokasi: {selection_location}\n\nPersiapan wajib:\n- Download dan bawa Kartu Peserta Seleksi.\n- Bawa ATK lengkap (pulpen, pensil, penghapus, dan alat tulis lain yang diperlukan).\n\nSilakan buka halaman cek status untuk informasi lengkap.",
        'selection_attending_test' => 'Status seleksi {registration_number} diperbarui: sedang mengikuti proses seleksi.',
        'selection_passed' => 'Selamat {full_name}! Anda dinyatakan DITERIMA pada PMB Pendidikan Kader Ulama. Nomor pendaftaran: {registration_number}. Silakan ikuti arahan lanjutan dari panitia.',
        'selection_not_passed' => 'Terima kasih {full_name} telah mengikuti seluruh proses. Berdasarkan hasil seleksi, pendaftaran {registration_number} belum dinyatakan diterima.',
        'selection_withdrawn' => 'Pendaftaran {registration_number} telah dibatalkan atau mengundurkan diri. Hubungi panitia apabila status ini tidak sesuai.',
    ];

    public function __construct(private SettingsService $settings) {}

    public function all(): array
    {
        return collect(self::DEFAULTS)->mapWithKeys(
            fn ($value, $key) => [$key => $this->settings->get('notifications.'.$key, $value)]
        )->all();
    }

    public function render(string $event, Applicant $applicant, ?string $fallback = null): string
    {
        $template = $this->settings->get(
            'notifications.'.$event,
            self::DEFAULTS[$event] ?? $fallback ?? "Assalamu'alaikum {full_name},\n\nTerdapat pembaruan status PMB untuk {registration_number}. Silakan cek status pendaftaran Anda."
        );
        $session = $event === 'selection_scheduled'
            ? $applicant->testSessions()->latest('starts_at')->first()
            : null;
        $date = $session?->starts_at?->locale('id')->translatedFormat('l, d F Y') ?? '-';
        $time = $session?->starts_at?->format('H:i') ?? '-';
        $location = trim((string) app(SettingsService::class)->get('pmb.selection_location', ''))
            ?: $session?->location
            ?: 'Lokasi akan diinformasikan oleh panitia';
        $containsScheduleVariables = str_contains($template, '{selection_date}') || str_contains($template, '{selection_time}') || str_contains($template, '{selection_location}');

        $message = strtr($template, [
            '{registration_number}' => $applicant->registration_number,
            '{full_name}' => $applicant->full_name,
            '{payment_status}' => $applicant->payment_status->value,
            '{document_status}' => $applicant->document_status->value,
            '{selection_status}' => $applicant->selection_status->value,
            '{payment_status_label}' => $this->label($applicant->payment_status->value),
            '{document_status_label}' => $this->label($applicant->document_status->value),
            '{selection_status_label}' => $this->label($applicant->selection_status->value),
            '{selection_date}' => $date,
            '{selection_time}' => $time,
            '{selection_location}' => $location,
        ]);

        if ($event === 'selection_scheduled' && ! $containsScheduleVariables) {
            $message .= "\n\nJadwal Seleksi:\nTanggal: {$date}\nWaktu: {$time} WIB\nLokasi: {$location}";
        }

        return $message;
    }

    private function label(string $value): string
    {
        return [
            'unpaid' => 'Belum dibayar', 'pending' => 'Menunggu konfirmasi', 'paid' => 'Lunas',
            'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'refunded' => 'Dikembalikan',
            'pending_review' => 'Menunggu pemeriksaan', 'revision_submitted' => 'Perbaikan dikirim',
            'incomplete' => 'Perlu perbaikan', 'complete' => 'Lengkap',
            'not_scheduled' => 'Belum dijadwalkan', 'scheduled' => 'Dijadwalkan',
            'attending_test' => 'Mengikuti seleksi', 'passed' => 'Diterima',
            'not_passed' => 'Tidak diterima', 'withdrawn' => 'Dibatalkan',
        ][$value] ?? str_replace('_', ' ', $value);
    }
}
