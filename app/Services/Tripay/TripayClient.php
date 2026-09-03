<?php

namespace App\Services\Tripay;

use App\Models\Applicant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TripayClient
{
    private function apiKey(): string
    {
        $apiKey = trim((string) config('services.tripay.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Tripay: API Key belum dikonfigurasi.');
        }

        return $apiKey;
    }

    private function credentials(): array
    {
        $merchant = (string) config('services.tripay.merchant_code');
        $apiKey = (string) config('services.tripay.api_key');
        $privateKey = (string) config('services.tripay.private_key');
        if ($merchant === '' || $apiKey === '' || $privateKey === '') throw new RuntimeException('Tripay belum dikonfigurasi.');
        return [$merchant, $apiKey, $privateKey];
    }

    private function base(): string
    {
        return config('services.tripay.mode') === 'production' ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
    }

    public function channels(int $amount): array
    {
        $response = Http::acceptJson()->withToken($this->apiKey())->timeout(20)->retry(2, 500, throw: false)
            ->get($this->base().'/merchant/payment-channel');
        $payload = $response->json() ?: [];
        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            $message = trim((string) ($payload['message'] ?? 'API Tripay tidak dapat dihubungi.'));
            throw new RuntimeException('Tripay: '.str($message)->limit(180));
        }

        $channels = collect($payload['data'] ?? [])
            ->filter(fn (array $channel) => ($channel['active'] ?? true)
                && $amount >= (int) ($channel['minimum_amount'] ?? 0)
                && ($amount <= (int) ($channel['maximum_amount'] ?? PHP_INT_MAX)))
            ->map(function (array $channel) use ($amount): array {
                $flatFee = (float) ($channel['fee_customer']['flat'] ?? 0);
                $percentageFee = (float) ($channel['fee_customer']['percent'] ?? 0);

                return [
                    'code' => $channel['code'] ?? '',
                    'name' => $channel['name'] ?? 'Pembayaran',
                    'group' => $channel['group'] ?? 'Tripay',
                    'icon_url' => $channel['icon_url'] ?? null,
                    'fee' => (int) round($flatFee + ($amount * $percentageFee / 100)),
                ];
            })
            ->filter(fn (array $channel) => $channel['code'] !== '')
            ->values()
            ->all();

        if ($channels === []) {
            throw new RuntimeException('Tripay: belum ada channel pembayaran aktif untuk nominal transaksi ini. Aktifkan channel pada dashboard Tripay.');
        }

        return $channels;
    }

    public function create(Applicant $applicant, string $method, string $merchantRef, int $amount): array
    {
        [$merchant, $apiKey, $privateKey] = $this->credentials();
        $payload = [
            'method' => $method, 'merchant_ref' => $merchantRef, 'amount' => $amount,
            'customer_name' => $applicant->full_name, 'customer_email' => $applicant->email,
            'customer_phone' => $applicant->whatsapp_normalized,
            'order_items' => [['sku' => 'PMB-PKU', 'name' => 'Biaya Pendaftaran PMB PKU', 'price' => $amount, 'quantity' => 1]],
            'callback_url' => url('/webhooks/tripay'), 'return_url' => route('status.index'),
            'expired_time' => now()->addDay()->timestamp,
            'signature' => hash_hmac('sha256', $merchant.$merchantRef.$amount, $privateKey),
        ];
        $response = Http::acceptJson()->withToken($apiKey)->asForm()->timeout(20)->retry(2, 500, throw: false)
            ->post($this->base().'/transaction/create', $payload)->throw()->json();
        if (! ($response['success'] ?? false)) throw new RuntimeException($response['message'] ?? 'Transaksi Tripay gagal dibuat.');
        return $response['data'] ?? [];
    }
}
