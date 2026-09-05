<?php

namespace App\Services;

use App\Models\Applicant;
use App\Services\Duitku\DuitkuClient;
use App\Services\Midtrans\MidtransClient;
use App\Services\Mayar\MayarClient;
use App\Services\Mayar\MayarLinkClient;
use App\Services\Tripay\TripayClient;

class PaymentGatewayService
{
    public function __construct(private DuitkuClient $duitku, private TripayClient $tripay, private MidtransClient $midtrans, private MayarClient $mayar, private MayarLinkClient $mayarLink, private SettingsService $settings) {}

    public function provider(): string
    {
        return (string) $this->settings->get('payment.provider', 'duitku');
    }

    public function mode(): string
    {
        return (string) $this->settings->get($this->provider().'.mode', 'sandbox');
    }

    public function channels(int $amount): array
    {
        return match ($this->provider()) {
            'tripay' => $this->tripay->channels($amount), 'midtrans' => $this->midtrans->channels($amount), 'mayar' => $this->mayar->channels($amount), 'mayar_link' => $this->mayarLink->channels($amount), default => $this->duitku->channels($amount)
        };
    }

    public function create(Applicant $applicant, string $method, string $reference, int $amount): array
    {
        return match ($this->provider()) {
            'tripay' => $this->tripay->create($applicant, $method, $reference, $amount), 'midtrans' => $this->midtrans->create($applicant, $method, $reference, $amount), 'mayar' => $this->mayar->create($applicant, $method, $reference, $amount), 'mayar_link' => $this->mayarLink->create($applicant, $method, $reference, $amount), default => $this->duitku->create($applicant, $method, $reference, $amount)
        };
    }
}
