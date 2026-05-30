<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorDevice;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    public function show(TwoFactorService $twoFactor): View|RedirectResponse
    {
        $device = $this->pendingDevice();

        if (! $device) {
            return redirect()->route('login')->with('error', 'Perangkat authenticator tidak ditemukan.');
        }

        $user = $this->setupUser($device);

        return view('auth.two-factor-setup', [
            'user' => $user,
            'device' => $device,
            'qrCode' => $twoFactor->qrCodeSvg($user, $device),
            'manualKey' => $twoFactor->manualKey($device),
            'fromAdmin' => (bool) session()->get(AuthSession::SETUP_FROM_ADMIN, false),
        ]);
    }

    public function confirm(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $device = $this->pendingDevice();

        if (! $device || ! $twoFactor->verifyDevice($device, $request->input('code'))) {
            return back()->with('error', 'Kode 6 digit tidak valid. Coba lagi.');
        }

        $user = $this->setupUser($device);
        $isFirstDevice = ! $user->hasConfirmedTwoFactor();

        $twoFactor->confirmDevice($device);

        if ((bool) session()->get(AuthSession::SETUP_FROM_ADMIN, false)) {
            session()->forget([AuthSession::PENDING_DEVICE_ID, AuthSession::SETUP_FROM_ADMIN]);

            return redirect()
                ->route('security.two-factor')
                ->with('success', "Perangkat \"{$device->label}\" berhasil ditambahkan.");
        }

        if ($isFirstDevice) {
            $codes = $twoFactor->generateRecoveryCodes($user);
            session([AuthSession::RECOVERY_CODES => $codes]);

            return redirect()->route('two-factor.recovery-codes');
        }

        return $this->completeLogin($user);
    }

    public function recoveryCodes(TwoFactorService $twoFactor): View|RedirectResponse
    {
        if (! session()->has(AuthSession::PENDING_USER_ID) || ! session()->has(AuthSession::RECOVERY_CODES)) {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session(AuthSession::PENDING_USER_ID));

        return view('auth.two-factor-recovery-codes', [
            'codes' => session(AuthSession::RECOVERY_CODES, []),
            'user' => $user,
        ]);
    }

    public function acknowledgeRecovery(): RedirectResponse
    {
        if (! session()->has(AuthSession::PENDING_USER_ID)) {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session(AuthSession::PENDING_USER_ID));

        session()->forget(AuthSession::RECOVERY_CODES);

        return $this->completeLogin($user);
    }

    private function pendingDevice(): ?TwoFactorDevice
    {
        $deviceId = session(AuthSession::PENDING_DEVICE_ID);

        if (! $deviceId) {
            return null;
        }

        return TwoFactorDevice::query()->find($deviceId);
    }

    private function setupUser(TwoFactorDevice $device): User
    {
        if (auth()->check()) {
            abort_unless(auth()->id() === $device->user_id, 403);

            return auth()->user();
        }

        $pendingUserId = session(AuthSession::PENDING_USER_ID);
        abort_unless($pendingUserId && (int) $pendingUserId === $device->user_id, 403);

        return User::findOrFail($pendingUserId);
    }

    private function completeLogin(User $user): RedirectResponse
    {
        session()->forget([
            AuthSession::PENDING_USER_ID,
            AuthSession::PENDING_DEVICE_ID,
            AuthSession::SETUP_FROM_ADMIN,
            AuthSession::RECOVERY_CODES,
        ]);

        Auth::login($user, (bool) session()->get(AuthSession::REMEMBER_LOGIN, false));
        session()->forget(AuthSession::REMEMBER_LOGIN);
        session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Login berhasil.');
    }
}
