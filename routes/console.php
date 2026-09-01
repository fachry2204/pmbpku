<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use App\Models\{Payment,OtpChallenge};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create {--name=} {--username=} {--email=}', function () {
    $name = $this->option('name') ?: $this->ask('Nama super admin');
    $username = strtolower($this->option('username') ?: $this->ask('Username super admin'));
    $email = strtolower($this->option('email') ?: $this->ask('Email super admin'));
    $password = $this->secret('Password (minimal 12 karakter)');
    if (strlen((string) $password) < 12) {
        $this->error('Password minimal 12 karakter.');
        return 1;
    }
    User::updateOrCreate(['email' => $email], ['name' => $name, 'username' => $username, 'password' => Hash::make($password), 'role' => 'super_admin', 'is_active' => true, 'must_change_password' => true]);
    $this->info('Super admin berhasil dibuat.');
    return 0;
})->purpose('Membuat super admin tanpa password bawaan');

Schedule::call(function(){Payment::whereIn('status',['unpaid','pending'])->where('expires_at','<',now())->update(['status'=>'expired']);})->everyTenMinutes()->name('expire-payments')->withoutOverlapping();
Schedule::call(function(){OtpChallenge::where('expires_at','<',now()->subDay())->delete();})->daily()->name('prune-otp');
Schedule::call(function(){\Illuminate\Support\Facades\Cache::put('queue.heartbeat',now(),now()->addMinutes(10));})->everyMinute()->name('queue-heartbeat');
