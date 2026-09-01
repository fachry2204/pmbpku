<?php
namespace Tests\Unit;
use App\Support\IndonesianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
class IndonesianPhoneTest extends TestCase { public static function phones():array{return [['081234567890','6281234567890'],['+62 812-3456-7890','6281234567890'],['81234567890','6281234567890']];} #[DataProvider('phones')] public function test_normalizes_indonesian_numbers(string $input,string $expected):void{$this->assertSame($expected,IndonesianPhone::normalize($input));} }
