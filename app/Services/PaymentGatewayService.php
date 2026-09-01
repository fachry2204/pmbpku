<?php

namespace App\Services;

use App\Models\Applicant;
use App\Services\Duitku\DuitkuClient;
use App\Services\Tripay\TripayClient;

class PaymentGatewayService
{
    public function __construct(private DuitkuClient $duitku, private TripayClient $tripay, private SettingsService $settings) {}
    public function provider(): string { return (string) $this->settings->get('payment.provider', 'duitku'); }
    public function mode(): string { return (string) $this->settings->get($this->provider().'.mode', 'sandbox'); }
    public function channels(int $amount): array { return $this->provider() === 'tripay' ? $this->tripay->channels($amount) : $this->duitku->channels($amount); }
    public function create(Applicant $applicant, string $method, string $reference, int $amount): array { return $this->provider() === 'tripay' ? $this->tripay->create($applicant, $method, $reference, $amount) : $this->duitku->create($applicant, $method, $reference, $amount); }
}
