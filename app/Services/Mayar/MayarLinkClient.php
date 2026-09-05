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

        $query = http_build_query([
            'email' => $applicant->email,
            'name' => $applicant->full_name,
            'mobile' => $applicant->whatsapp_display ?: $applicant->whatsapp_normalized,
            'phone' => $applicant->whatsapp_display ?: $applicant->whatsapp_normalized,
            'registration_number' => $applicant->registration_number,
            'nomor_pendaftaran' => $applicant->registration_number,
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'reference' => $reference,
            'paymentMethod' => 'mayar_link',
            'amount' => $amount,
            'paymentUrl' => $url.($query !== '' ? '?'.$query : ''),
        ];
    }
}
