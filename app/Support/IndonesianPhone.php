<?php
namespace App\Support;
use InvalidArgumentException;
final class IndonesianPhone {
    public static function normalize(string $value): string {
        $digits=preg_replace('/\D+/', '', $value) ?? '';
        if(str_starts_with($digits,'0')) $digits='62'.substr($digits,1);
        elseif(!str_starts_with($digits,'62')) $digits='62'.$digits;
        if(!preg_match('/^62\d{7,13}$/',$digits)) throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        return $digits;
    }
}
