<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(SettingsService::class)->get('registration.open', true)) {
            return redirect()->route('registration.create', ['registration_closed' => 1]);
        }

        return $next($request);
    }
}
