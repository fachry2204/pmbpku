<?php

namespace App\Services\Midtrans;

use App\Models\Applicant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransClient
{
    private function serverKey(): string
    {
        $key = (string) config('services.midtrans.server_key');
        if ($key === '') {
            throw new RuntimeException('Midtrans belum dikonfigurasi. Isi Server Key terlebih dahulu.');
        }

        return $key;
    }

    private function base(): string
    {
        return config('services.midtrans.mode') === 'production'
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    public function channels(int $amount): array
    {
        $this->serverKey();

        return [[
            'code' => 'MIDTRANS',
            'name' => 'Midtrans',
            'group' => 'VA, QRIS, E-Wallet, Kartu & lainnya',
            'icon_url' => null,
            'fee' => 0,
        ]];
    }

    public function create(Applicant $applicant, string $method, string $orderId, int $amount): array
    {
        if ($method !== 'MIDTRANS') {
            throw new RuntimeException('Metode Midtrans tidak valid.');
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => [[
                'id' => 'PMB-PKU',
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Biaya Pendaftaran PMB PKU',
            ]],
            'customer_details' => [
                'first_name' => mb_substr($applicant->full_name, 0, 50),
                'email' => $applicant->email,
                'phone' => $applicant->whatsapp_normalized,
            ],
            'callbacks' => [
                'finish' => route('status.index'),
            ],
            'expiry' => [
                'unit' => 'day',
                'duration' => 1,
            ],
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->withBasicAuth($this->serverKey(), '')
            ->timeout(20)
            ->retry(2, 500, throw: false)
            ->post($this->base().'/snap/v1/transactions', $payload);
        $data = $response->json() ?: [];

        if (! $response->successful() || empty($data['token']) || empty($data['redirect_url'])) {
            $message = $data['error_messages'][0] ?? $data['status_message'] ?? 'Transaksi ditolak oleh Midtrans.';
            throw new RuntimeException('Midtrans: '.str($message)->limit(300));
        }

        return [
            'reference' => null,
            'paymentMethod' => 'MIDTRANS',
            'amount' => $amount,
            'paymentUrl' => $data['redirect_url'],
            'token' => $data['token'],
        ];
    }
}
