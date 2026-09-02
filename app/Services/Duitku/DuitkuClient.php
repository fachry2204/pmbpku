<?php

namespace App\Services\Duitku;

use App\Models\Applicant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DuitkuClient
{
    private function credentials(): array
    {
        $merchant = (string) config('services.duitku.merchant_code');
        $apiKey = (string) config('services.duitku.api_key');
        if ($merchant === '' || $apiKey === '') throw new RuntimeException('Duitku belum dikonfigurasi.');
        return [$merchant, $apiKey];
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(20)->retry(2, 500, throw: false);
    }

    private function base(): string
    {
        return config('services.duitku.mode') === 'production' ? 'https://passport.duitku.com' : 'https://sandbox.duitku.com';
    }

    public function channels(int $amount): array
    {
        [$merchant, $apiKey] = $this->credentials();
        $datetime = now()->format('Y-m-d H:i:s');
        $response = $this->http()->post($this->base().'/webapi/api/merchant/paymentmethod/getpaymentmethod', [
            'merchantcode' => $merchant,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => DuitkuSignature::paymentMethods($merchant, $amount, $datetime, $apiKey),
        ]);
        $data = $response->json() ?: [];
        if (! $response->successful() || ($data['responseCode'] ?? '') !== '00') {
            $message = $data['responseMessage'] ?? $data['Message'] ?? 'Daftar metode pembayaran tidak tersedia.';
            throw new RuntimeException('Duitku: '.str($message)->limit(300));
        }

        return collect($data['paymentFee'] ?? [])->map(fn (array $channel) => [
            'code' => $channel['paymentMethod'] ?? '',
            'name' => $channel['paymentName'] ?? $channel['paymentMethod'] ?? 'Pembayaran',
            'group' => $channel['paymentGroup'] ?? 'Duitku',
            'icon_url' => $channel['paymentImage'] ?? null,
            'fee' => (int) ($channel['totalFee'] ?? 0),
        ])->filter(fn (array $channel) => $channel['code'] !== '')->values()->all();
    }

    public function create(Applicant $applicant, string $method, string $merchantOrderId, int $amount): array
    {
        [$merchant, $apiKey] = $this->credentials();
        if (! preg_match('/^[A-Za-z0-9]{2}$/', $method)) {
            throw new RuntimeException('Duitku: kode metode pembayaran tidak valid.');
        }
        $localPhone = preg_replace('/^62/', '0', $applicant->whatsapp_normalized);
        $names = preg_split('/\s+/', trim($applicant->full_name), 2);
        $payload = [
            'merchantCode' => $merchant,
            'paymentAmount' => $amount,
            'paymentMethod' => $method,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Biaya Pendaftaran PMB '.$applicant->registration_number,
            'additionalParam' => http_build_query(['registration_number' => $applicant->registration_number]),
            'merchantUserInfo' => $applicant->registration_number,
            'email' => $applicant->email,
            'phoneNumber' => $localPhone,
            'customerVaName' => mb_substr($applicant->full_name, 0, 20),
            'itemDetails' => [[
                'name' => 'Biaya Pendaftaran PMB PKU',
                'price' => $amount,
                'quantity' => 1,
            ]],
            'customerDetail' => [
                'firstName' => mb_substr($names[0] ?? $applicant->full_name, 0, 50),
                'lastName' => mb_substr($names[1] ?? '', 0, 50),
                'email' => $applicant->email,
                'phoneNumber' => $localPhone,
            ],
            'callbackUrl' => route('webhooks.duitku'),
            'returnUrl' => route('registration.success', $applicant->registration_number),
            'signature' => DuitkuSignature::inquiry($merchant, $merchantOrderId, $amount, $apiKey),
            'expiryPeriod' => 1440,
        ];
        $response = $this->http()->post($this->base().'/webapi/api/merchant/v2/inquiry', $payload);
        $data = $response->json() ?: [];
        if (! $response->successful() || ($data['statusCode'] ?? '') !== '00' || empty($data['reference']) || empty($data['paymentUrl'])) {
            $message = $data['statusMessage'] ?? $data['Message'] ?? $data['message'] ?? 'Transaksi ditolak oleh Duitku.';
            throw new RuntimeException('Duitku: '.str($message)->limit(300));
        }
        $paymentHost = strtolower((string) parse_url((string) $data['paymentUrl'], PHP_URL_HOST));
        if (($data['merchantCode'] ?? '') !== $merchant || (int) ($data['amount'] ?? -1) !== $amount || ! in_array($paymentHost, ['sandbox.duitku.com', 'passport.duitku.com'], true)) {
            throw new RuntimeException('Duitku: respons transaksi tidak valid.');
        }
        return $data;
    }
}
