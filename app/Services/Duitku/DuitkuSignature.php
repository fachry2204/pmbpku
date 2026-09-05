<?php

namespace App\Services\Duitku;

final class DuitkuSignature
{
    public static function createInvoice(string $merchantCode, string $timestamp, string $apiKey): string
    {
        return hash_hmac('sha256', $merchantCode.$timestamp, $apiKey);
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
