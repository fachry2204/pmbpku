<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureActiveAdmin { public function handle(Request $request,Closure $next,...$roles):Response { $user=$request->user();abort_unless($user&&$user->is_active,403);if($roles)abort_unless(in_array($user->role,$roles,true)||$user->role==='super_admin',403);return $next($request); } }
