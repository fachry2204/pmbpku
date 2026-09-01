<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\RcloneStorageService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SettingsController extends Controller
{
    private const KEYS = [
        'pmb.registration_fee' => ['integer', false],
        'pmb.registration_year' => ['integer', false],
        'registration.document_upload_disabled' => ['boolean', false],
        'scores.label_1' => ['string', false],
        'scores.label_2' => ['string', false],
        'scores.label_3' => ['string', false],
        'scores.label_4' => ['string', false],
        'storage.google_drive_enabled' => ['boolean', false],
        'rclone.binary_path' => ['string', false],
        'rclone.remote' => ['string', false],
        'rclone.config_path' => ['string', false],
        'rclone.root_folder' => ['string', false],
        'payment.provider' => ['string', false],
        'duitku.mode' => ['string', false],
        'duitku.merchant_code' => ['string', false],
        'duitku.api_key' => ['string', true],
        'tripay.mode' => ['string', false],
        'tripay.merchant_code' => ['string', false],
        'tripay.api_key' => ['string', true],
        'tripay.private_key' => ['string', true],
        'midtrans.mode' => ['string', false],
        'midtrans.server_key' => ['string', true],
        'midtrans.client_key' => ['string', true],
        'fonnte.base_url' => ['string', false],
        'fonnte.token' => ['string', true],
        'notifications.whatsapp_enabled' => ['boolean', false],
        'notifications.email_enabled' => ['boolean', false],
        'mail.host' => ['string', false],
        'mail.port' => ['integer', false],
        'mail.username' => ['string', true],
        'mail.password' => ['string', true],
        'mail.from_address' => ['string', false],
        'mail.from_name' => ['string', false],
    ];

    public function index(): Response
    {
        $stored = Setting::whereIn('key', array_keys(self::KEYS))->get()->keyBy('key');
        $values = [];
        $unreadableSettings = [];
        foreach (self::KEYS as $key => [$type, $secret]) {
            $row = $stored->get($key);
            $default = match ($key) {
                'pmb.registration_fee' => 250000,
                'pmb.registration_year' => now()->year,
                'registration.document_upload_disabled' => false,
                'scores.label_1' => 'Tes Tulis Wawasan Keislaman',
                'scores.label_2' => 'Membaca Al Qur’an',
                'scores.label_3' => 'Qiroatul Kutub & Muhadatsah Bahasa Arab',
                'scores.label_4' => 'Wawancara',
                'storage.google_drive_enabled' => false,
                'rclone.binary_path' => '/usr/local/bin/rclone',
                'rclone.remote' => 'gdrive',
                'rclone.root_folder' => 'PMB-PKU',
                'payment.provider' => 'duitku',
                'duitku.mode', 'tripay.mode', 'midtrans.mode' => 'sandbox',
                'notifications.whatsapp_enabled', 'notifications.email_enabled' => true,
                default => '',
            };
            try {
                $value = $row?->getDecodedValue() ?? $default;
                $values[$key] = $secret ? ($row ? '••••••••' : '') : ($key === 'mail.port' && (int) $value === 0 ? '' : $value);
            } catch (Throwable) {
                // A changed APP_KEY or damaged ciphertext must not make the
                // entire administration page unavailable. Ask for this one
                // credential again without exposing its stored value.
                $values[$key] = $secret ? '' : $default;
                $unreadableSettings[] = $key;
            }
        }

        return Inertia::render('Admin/Settings/Index', [
            'values' => $values,
            'callbackUrl' => route('webhooks.duitku'),
            // Build public integration URLs directly so the settings screen can
            // still open while a deployment is replacing an older route cache.
            'tripayCallbackUrl' => url('/webhooks/tripay'),
            'midtransCallbackUrl' => url('/webhooks/midtrans'),
            'returnUrl' => route('status.index'),
            'unreadableSettings' => $unreadableSettings,
        ]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        // An empty number input can arrive as 0 from older saved settings. SMTP is
        // optional, so do not let that invalidate and roll back unrelated settings.
        if (in_array($request->input('mail_port'), [null, '', 0, '0'], true)) {
            $request->merge(['mail_port' => null]);
        }

        $data = $request->validate([
            'pmb_registration_fee' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'pmb_registration_year' => ['sometimes', 'required', 'integer', 'min:2020', 'max:2100'],
            'registration_document_upload_disabled' => ['required', 'boolean'],
            'scores_label_1' => ['sometimes', 'required', 'string', 'max:80'],
            'scores_label_2' => ['sometimes', 'required', 'string', 'max:80'],
            'scores_label_3' => ['sometimes', 'required', 'string', 'max:80'],
            'scores_label_4' => ['sometimes', 'required', 'string', 'max:80'],
            'storage_google_drive_enabled' => ['sometimes', 'required', 'boolean'],
            'rclone_binary_path' => ['nullable', 'string', 'max:500'],
            'rclone_remote' => ['nullable', 'regex:/^[A-Za-z0-9_-]+$/', 'max:100'],
            'rclone_config_path' => ['nullable', 'string', 'max:500'],
            'rclone_root_folder' => ['nullable', 'string', 'max:200'],
            'payment_provider' => ['required', 'in:duitku,tripay,midtrans'],
            'duitku_mode' => ['nullable', 'in:sandbox,production'],
            'duitku_merchant_code' => ['nullable', 'string', 'max:100'],
            'duitku_api_key' => ['nullable', 'string', 'max:500'],
            'tripay_mode' => ['nullable', 'in:sandbox,production'],
            'tripay_merchant_code' => ['nullable', 'string', 'max:100'],
            'tripay_api_key' => ['nullable', 'string', 'max:500'],
            'tripay_private_key' => ['nullable', 'string', 'max:500'],
            'midtrans_mode' => ['nullable', 'in:sandbox,production'],
            'midtrans_server_key' => ['nullable', 'string', 'max:500'],
            'midtrans_client_key' => ['nullable', 'string', 'max:500'],
            'fonnte_base_url' => ['nullable', 'url', 'max:500'],
            'fonnte_token' => ['nullable', 'string', 'max:500'],
            'notifications_whatsapp_enabled' => ['required', 'boolean'],
            'notifications_email_enabled' => ['required', 'boolean'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_from_address' => ['nullable', 'email', 'max:190'],
            'mail_from_name' => ['nullable', 'string', 'max:190'],
        ]);

        foreach (self::KEYS as $key => [$type, $secret]) {
            $input = str_replace('.', '_', $key);
            if (! array_key_exists($input, $data) || ($secret && blank($data[$input])) || $data[$input] === '••••••••') {
                continue;
            }
            $settings->put(strtok($key, '.'), $key, $data[$input], $type, $secret);
        }

        return back()->with('success', 'Pengaturan tersimpan.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_email_recipient' => ['required', 'email', 'max:190']]);

        try {
            Mail::mailer('smtp')->raw(
                'Email percobaan berhasil dikirim. Konfigurasi SMTP PMB PKU MUI Provinsi DKI Jakarta sudah berfungsi.',
                fn ($message) => $message->to($data['test_email_recipient'])->subject('Tes Konfigurasi Email PMB PKU')
            );

            return back()->with('success', 'Email percobaan berhasil dikirim ke '.$data['test_email_recipient'].'.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Email gagal dikirim. Periksa host, port, username, app password, dan alamat pengirim. Detail: '.str($exception->getMessage())->limit(240));
        }
    }

    public function testDrive(RcloneStorageService $drive): RedirectResponse
    {
        try {
            $drive->testConnection();

            return back()->with('success', 'Koneksi rclone ke Google Drive berhasil.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Koneksi Google Drive gagal. Periksa lokasi rclone, config, dan nama remote. Detail: '.str($exception->getMessage())->limit(240));
        }
    }
}
