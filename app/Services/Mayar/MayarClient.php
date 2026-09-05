<?php

namespace App\Services\Mayar;

use App\Models\Applicant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MayarClient
{
    private function apiKey(): string
    {
        $key = trim((string) config('services.mayar.api_key'));
        if ($key === '') {
            throw new RuntimeException('Mayar: API Key belum dikonfigurasi.');
        }
        return $key;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->withToken($this->apiKey())->timeout(20)->retry(2, 500, throw: false);
    }

    private function base(): string
    {
        return config('services.mayar.mode') === 'production' ? 'https://api.mayar.id/hl/v2' : 'https://api.mayar.io/hl/v2';
    }

    public function channels(int $amount): array
    {
        $this->apiKey();
        return [[
            'code' => 'MAYAR', 'name' => 'Mayar', 'group' => 'VA, QRIS, E-Wallet & lainnya', 'icon_url' => null, 'fee' => 0,
        ]];
    }

    public function create(Applicant $applicant, string $method, string $merchantRef, int $amount): array
    {
        if ($method !== 'MAYAR') {
            throw new RuntimeException('Mayar: metode pembayaran tidak valid.');
        }

        $response = $this->http()->post($this->base().'/invoices/create', [
            'name' => $applicant->full_name,
            'email' => $applicant->email,
            'mobile' => $applicant->whatsapp_normalized,
            'redirectUrl' => route('status.index'),
            'description' => 'Biaya Pendaftaran PMB '.$applicant->registration_number,
            'expiredAt' => now()->addDay()->utc()->format('Y-m-d\\TH:i:s.v\\Z'),
            'items' => [[
                'quantity' => 1,
                'rate' => $amount,
                'description' => 'Biaya Pendaftaran PMB PKU',
            ]],
            'extraData' => [
                'noCustomer' => $merchantRef,
                'idProd' => 'PMB-PKU',
            ],
        ]);
        $payload = $response->json() ?: [];
        $data = $payload['data'] ?? [];
        if (! $response->successful() || (int) ($payload['statusCode'] ?? 0) !== 200 || empty($data['link'])) {
            throw new RuntimeException('Mayar: '.str((string) ($payload['messages'] ?? 'Invoice gagal dibuat.'))->limit(300));
        }

        return [
            'reference' => $data['transactionId'] ?? $data['id'] ?? $merchantRef,
            'paymentMethod' => 'MAYAR',
            'amount' => $amount,
            'paymentUrl' => $data['link'],
            'mayar_invoice_id' => $data['id'] ?? null,
            'mayar_transaction_id' => $data['transactionId'] ?? null,
            'statusCode' => $payload['statusCode'] ?? null,
            'statusMessage' => $payload['messages'] ?? null,
        ];
    }
}
