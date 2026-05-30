<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorSetupAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasPendingLogin = session()->has(AuthSession::PENDING_USER_ID);
        $addingFromAdmin = auth()->check() && session()->has(AuthSession::PENDING_DEVICE_ID);

        if (! $hasPendingLogin && ! $addingFromAdmin) {
            return redirect()->route('login')->with('error', 'Sesi verifikasi kedaluwarsa. Silakan login ulang.');
        }

        return $next($request);
    }
}
