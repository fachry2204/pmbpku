<?php

namespace App\Services\Duitku;

final class DuitkuSignature
{
    public static function paymentMethods(string $merchantCode, int $amount, string $datetime, string $apiKey): string
    {
        return hash_hmac('sha256', $merchantCode.$amount.$datetime, $apiKey);
    }

    public static function inquiry(string $merchantCode, string $merchantOrderId, int $amount, string $apiKey): string
    {
        return hash_hmac('sha256', $merchantCode.$merchantOrderId.$amount, $apiKey);
    }

    public static function callback(string $merchantCode, int $amount, string $merchantOrderId, string $apiKey): string
    {
        return hash_hmac('sha256', $merchantCode.$amount.$merchantOrderId, $apiKey);
    }

    public static function validCallback(string $merchantCode, int $amount, string $merchantOrderId, string $signature, string $apiKey): bool
    {
        return hash_equals(self::callback($merchantCode, $amount, $merchantOrderId, $apiKey), $signature);
    }
}
