<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePendingAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has(AuthSession::PENDING_USER_ID)) {
            return redirect()->route('login')->with('error', 'Sesi login kedaluwarsa. Silakan login ulang.');
        }

        return $next($request);
    }
}
