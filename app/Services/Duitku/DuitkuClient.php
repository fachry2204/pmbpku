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
        ])->throw()->json();

        return collect($response['paymentFee'] ?? [])->map(fn (array $channel) => [
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
        $localPhone = preg_replace('/^62/', '0', $applicant->whatsapp_normalized);
        $names = preg_split('/\s+/', trim($applicant->full_name), 2);
        $payload = [
            'merchantCode' => $merchant,
            'paymentAmount' => $amount,
            'paymentMethod' => $method,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Biaya Pendaftaran PMB '.$applicant->registration_number,
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
            'returnUrl' => route('status.index'),
            'signature' => DuitkuSignature::inquiry($merchant, $merchantOrderId, $amount, $apiKey),
            'expiryPeriod' => 1440,
        ];
        $response = $this->http()->post($this->base().'/webapi/api/merchant/v2/inquiry', $payload);
        $data = $response->json() ?: [];
        if (! $response->successful() || ($data['statusCode'] ?? '') !== '00' || empty($data['reference']) || empty($data['paymentUrl'])) {
            $message = $data['statusMessage'] ?? $data['Message'] ?? $data['message'] ?? 'Transaksi ditolak oleh Duitku.';
            throw new RuntimeException('Duitku: '.str($message)->limit(300));
        }
        return $data;
    }
}
