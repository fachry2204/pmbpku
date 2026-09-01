<?php

namespace Tests\Unit;

use App\Services\Duitku\DuitkuSignature;
use PHPUnit\Framework\TestCase;

class DuitkuSignatureTest extends TestCase
{
    public function test_signatures_use_hmac_sha256(): void
    {
        $this->assertSame(hash_hmac('sha256', 'D123INV1250000', 'secret'), DuitkuSignature::inquiry('D123', 'INV1', 250000, 'secret'));
        $signature = hash_hmac('sha256', 'D123250000INV1', 'secret');
        $this->assertTrue(DuitkuSignature::validCallback('D123', 250000, 'INV1', $signature, 'secret'));
        $this->assertFalse(DuitkuSignature::validCallback('D123', 250000, 'INV1', 'invalid', 'secret'));
    }
}
