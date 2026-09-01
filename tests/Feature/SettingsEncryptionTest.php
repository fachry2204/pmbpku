<?php
namespace Tests\Feature;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SettingsEncryptionTest extends TestCase { use RefreshDatabase; public function test_secret_is_encrypted_and_not_serialized():void{$service=app(SettingsService::class);$service->put('duitku','duitku.api_key','super-secret','string',true);$row=Setting::firstOrFail();$this->assertStringNotContainsString('super-secret',$row->getRawOriginal('value'));$this->assertSame('super-secret',$service->get('duitku.api_key'));$this->assertArrayNotHasKey('value',$row->toArray());} }

