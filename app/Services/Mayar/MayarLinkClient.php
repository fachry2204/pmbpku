<?php

namespace App\Services\Mayar;

use App\Models\Applicant;
use App\Services\SettingsService;

/**
 * Mayar Link checkout does not require an API request. The link page accepts
 * customer values through its query string, so the registration can continue
 * directly to the merchant's published payment form.
 */
class MayarLinkClient
{
    public function __construct(private SettingsService $settings) {}

    public function url(): string
    {
        return rtrim((string) $this->settings->get('mayar_link.url', config('services.mayar_link.url')), '/');
    }

    public function channels(int $amount): array
    {
        return [[
            'code' => 'mayar_link',
            'name' => 'Mayar Link',
            'group' => 'Mayar',
            'icon_url' => null,
        ]];
    }

    public function create(Applicant $applicant, string $method, string $reference, int $amount): array
    {
        $url = $this->url();
        if ($url === '') {
            throw new \RuntimeException('Mayar Link: URL pembayaran belum dikonfigurasi.');
        }

        $queryData = [
            'email' => $applicant->email,
            'name' => $applicant->full_name,
            'mobile' => $applicant->whatsapp_display ?: $applicant->whatsapp_normalized,
            'phone' => $applicant->whatsapp_display ?: $applicant->whatsapp_normalized,
            'registration_number' => $applicant->registration_number,
            'nomor_pendaftaran' => $applicant->registration_number,
            // Mayar custom-form versions use different names depending on
            // when the field was created. Unknown parameters are ignored by
            // the checkout page, while these aliases keep older links working.
            'registrationNumber' => $applicant->registration_number,
            'Nomor Pendaftaran' => $applicant->registration_number,
            'custom_field' => $applicant->registration_number,
            // Mayar versions that support a per-session return URL will use
            // this to show the local verification notice after checkout.
            'redirectUrl' => route('payment.mayar-link.pending'),
        ];
        $fieldKey = trim((string) config('services.mayar_link.registration_field_key'));
        if ($fieldKey !== '') {
            $queryData[$fieldKey] = $applicant->registration_number;
        }
        $query = http_build_query($queryData, '', '&', PHP_QUERY_RFC3986);

        return [
            'reference' => $reference,
            'paymentMethod' => 'mayar_link',
            'amount' => $amount,
            'paymentUrl' => $url.($query !== '' ? '?'.$query : ''),
        ];
    }
}
