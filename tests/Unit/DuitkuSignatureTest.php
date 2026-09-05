<?php

namespace Tests\Unit;

use App\Services\Duitku\DuitkuSignature;
use PHPUnit\Framework\TestCase;

class DuitkuSignatureTest extends TestCase
{
    public function test_signatures_use_hmac_sha256(): void
    {
        $this->assertSame(hash_hmac('sha256', 'D1231773728479616', 'secret'), DuitkuSignature::createInvoice('D123', '1773728479616', 'secret'));
        $signature = hash_hmac('sha256', 'D123250000INV1', 'secret');
        $this->assertTrue(DuitkuSignature::validCallback('D123', 250000, 'INV1', $signature, 'secret'));
        $this->assertFalse(DuitkuSignature::validCallback('D123', 250000, 'INV1', 'invalid', 'secret'));
    }
}
