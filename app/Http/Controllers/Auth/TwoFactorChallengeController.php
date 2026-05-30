<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! session()->has(AuthSession::PENDING_USER_ID)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|min:6|max:12',
        ]);

        if (! session()->has(AuthSession::PENDING_USER_ID)) {
            return redirect()->route('login')->with('error', 'Sesi login kedaluwarsa.');
        }

        /** @var User $user */
        $user = User::findOrFail(session(AuthSession::PENDING_USER_ID));
        $code = trim($request->input('code'));

        $verified = strlen(preg_replace('/\D/', '', $code)) === 6
            ? $twoFactor->verifyForUser($user, preg_replace('/\D/', '', $code))
            : $twoFactor->consumeRecoveryCode($user, $code);

        if (! $verified) {
            return back()->with('error', 'Kode authenticator atau recovery code tidak valid.');
        }

        session()->forget([
            AuthSession::PENDING_USER_ID,
            AuthSession::PENDING_DEVICE_ID,
            AuthSession::RECOVERY_CODES,
        ]);

        Auth::login($user, (bool) session()->get(AuthSession::REMEMBER_LOGIN, false));
        session()->forget(AuthSession::REMEMBER_LOGIN);
        session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Login berhasil.');
    }
}
