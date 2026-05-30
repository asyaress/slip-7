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

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::validate($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();

        Auth::logout();

        session([
            AuthSession::PENDING_USER_ID => $user->id,
            AuthSession::REMEMBER_LOGIN => $request->boolean('remember'),
        ]);

        if (! $user->hasConfirmedTwoFactor()) {
            $user->twoFactorDevices()->whereNull('confirmed_at')->delete();
            $device = $twoFactor->createPendingDevice($user, 'Authenticator Utama');
            session([AuthSession::PENDING_DEVICE_ID => $device->id]);

            return redirect()->route('two-factor.setup');
        }

        return redirect()->route('two-factor.challenge');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah logout.');
    }
}
