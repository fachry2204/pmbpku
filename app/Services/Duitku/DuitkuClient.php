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
        return config('services.duitku.mode') === 'production' ? 'https://api-prod.duitku.com' : 'https://api-sandbox.duitku.com';
    }

    public function channels(int $amount): array
    {
        $this->credentials();

        // POP menampilkan metode yang aktif untuk merchant di halaman Duitku.
        return [[
            'code' => 'POP',
            'name' => 'Semua Metode Pembayaran',
            'group' => 'Duitku POP',
            'icon_url' => null,
            'fee' => 0,
        ]];
    }

    public function create(Applicant $applicant, string $method, string $merchantOrderId, int $amount): array
    {
        [$merchant, $apiKey] = $this->credentials();
        if ($method !== 'POP' && ! preg_match('/^[A-Za-z0-9]{2}$/', $method)) {
            throw new RuntimeException('Duitku: kode metode pembayaran tidak valid.');
        }
        $timestamp = (string) round(microtime(true) * 1000);
        $localPhone = preg_replace('/^62/', '0', $applicant->whatsapp_normalized);
        $names = preg_split('/\s+/', trim($applicant->full_name), 2);
        $payload = [
            'paymentAmount' => $amount,
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
            'expiryPeriod' => (int) config('services.duitku.expiry_period', 60),
        ];
        if ($method !== 'POP') {
            $payload['paymentMethod'] = $method;
        }
        $response = $this->http()->withHeaders([
            'x-duitku-timestamp' => $timestamp,
            'x-duitku-signature' => DuitkuSignature::createInvoice($merchant, $timestamp, $apiKey),
            'x-duitku-merchantcode' => $merchant,
        ])->post($this->base().'/api/merchant/createInvoice', $payload);
        $data = $response->json() ?: [];
        if (! $response->successful() || ($data['statusCode'] ?? '') !== '00' || empty($data['reference']) || empty($data['paymentUrl'])) {
            $message = $data['statusMessage'] ?? $data['Message'] ?? $data['message'] ?? 'Transaksi ditolak oleh Duitku.';
            throw new RuntimeException('Duitku: '.str($message)->limit(300));
        }
        $paymentHost = strtolower((string) parse_url((string) $data['paymentUrl'], PHP_URL_HOST));
        $allowedHost = config('services.duitku.mode') === 'production' ? 'app-prod.duitku.com' : 'app-sandbox.duitku.com';
        if (($data['merchantCode'] ?? '') !== $merchant || $paymentHost !== $allowedHost) {
            throw new RuntimeException('Duitku: respons transaksi tidak valid.');
        }
        return $data;
    }
}
