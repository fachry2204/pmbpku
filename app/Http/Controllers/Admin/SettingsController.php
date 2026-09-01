<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        'duitku.mode' => ['string', false],
        'duitku.merchant_code' => ['string', false],
        'duitku.api_key' => ['string', true],
        'fonnte.base_url' => ['string', false],
        'fonnte.token' => ['string', true],
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
        foreach (self::KEYS as $key => [$type, $secret]) {
            $row = $stored->get($key);
            $default = $key === 'pmb.registration_fee' ? 250000 : '';
            $value = $row?->getDecodedValue() ?? $default;
            $values[$key] = $secret ? ($row ? '••••••••' : '') : ($key === 'mail.port' && (int) $value === 0 ? '' : $value);
        }
        return Inertia::render('Admin/Settings/Index', [
            'values' => $values,
            'callbackUrl' => route('webhooks.duitku'),
            'returnUrl' => route('status.index'),
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
            'duitku_mode' => ['nullable', 'in:sandbox,production'],
            'duitku_merchant_code' => ['nullable', 'string', 'max:100'],
            'duitku_api_key' => ['nullable', 'string', 'max:500'],
            'fonnte_base_url' => ['nullable', 'url', 'max:500'],
            'fonnte_token' => ['nullable', 'string', 'max:500'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_from_address' => ['nullable', 'email', 'max:190'],
            'mail_from_name' => ['nullable', 'string', 'max:190'],
        ]);

        foreach (self::KEYS as $key => [$type, $secret]) {
            $input = str_replace('.', '_', $key);
            if (! array_key_exists($input, $data) || ($secret && blank($data[$input])) || $data[$input] === '••••••••') continue;
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
}
