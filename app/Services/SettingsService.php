<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }try {
                return $setting->getDecodedValue() ?? $default;
            } catch (Throwable $exception) {
                report($exception);

                return $default;
            }
        });
    }

    public function put(string $group, string $key, mixed $value, string $type = 'string', bool $encrypted = false): void
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->fill(['group' => $group, 'type' => $type, 'is_encrypted' => $encrypted]);
        $setting->setDecodedValue($value);
        $setting->save();
        Cache::forget("setting.{$key}");
    }
}
