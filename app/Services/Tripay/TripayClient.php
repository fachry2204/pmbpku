<?php

namespace App\Services\Tripay;

use App\Models\Applicant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TripayClient
{
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
        [, $apiKey] = $this->credentials();
        $response = Http::acceptJson()->withToken($apiKey)->timeout(20)->retry(2, 500, throw: false)
            ->get($this->base().'/merchant/payment-channel')->throw()->json();
        if (! ($response['success'] ?? false)) throw new RuntimeException($response['message'] ?? 'Channel Tripay tidak tersedia.');

        return collect($response['data'] ?? [])->map(fn (array $channel) => [
            'code' => $channel['code'] ?? '', 'name' => $channel['name'] ?? 'Pembayaran',
            'group' => $channel['group'] ?? 'Tripay', 'icon_url' => $channel['icon_url'] ?? null,
            'fee' => (int) ($channel['total_fee']['customer'] ?? 0),
        ])->filter(fn (array $channel) => $channel['code'] !== '')->values()->all();
    }

    public function create(Applicant $applicant, string $method, string $merchantRef, int $amount): array
    {
        [$merchant, $apiKey, $privateKey] = $this->credentials();
        $payload = [
            'method' => $method, 'merchant_ref' => $merchantRef, 'amount' => $amount,
            'customer_name' => $applicant->full_name, 'customer_email' => $applicant->email,
            'customer_phone' => $applicant->whatsapp_normalized,
            'order_items' => [['sku' => 'PMB-PKU', 'name' => 'Biaya Pendaftaran PMB PKU', 'price' => $amount, 'quantity' => 1]],
            'callback_url' => route('webhooks.tripay'), 'return_url' => route('status.index'),
            'expired_time' => now()->addDay()->timestamp,
            'signature' => hash_hmac('sha256', $merchant.$merchantRef.$amount, $privateKey),
        ];
        $response = Http::acceptJson()->withToken($apiKey)->asForm()->timeout(20)->retry(2, 500, throw: false)
            ->post($this->base().'/transaction/create', $payload)->throw()->json();
        if (! ($response['success'] ?? false)) throw new RuntimeException($response['message'] ?? 'Transaksi Tripay gagal dibuat.');
        return $response['data'] ?? [];
    }
}
