<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        if (Schema::hasTable('settings')) {
            $settings = app(SettingsService::class);
            foreach (['duitku.mode','duitku.merchant_code','duitku.api_key','fonnte.base_url','fonnte.token'] as $key) {
                if (($value = $settings->get($key)) !== null) config()->set('services.'.$key, $value);
            }
            foreach (['mail.host'=>'mail.mailers.smtp.host','mail.port'=>'mail.mailers.smtp.port','mail.username'=>'mail.mailers.smtp.username','mail.password'=>'mail.mailers.smtp.password','mail.from_address'=>'mail.from.address'] as $key=>$target) {
                if (($value = $settings->get($key)) !== null) config()->set($target, $value);
            }
        }
    }
}
