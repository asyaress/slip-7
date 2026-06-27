<?php

namespace App\Http\Controllers;

use App\Models\TwoFactorDevice;
use App\Services\TwoFactorService;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorDeviceController extends Controller
{
    public function index(): View
    {
        $devices = auth()->user()
            ->twoFactorDevices()
            ->orderByDesc('confirmed_at')
            ->orderByDesc('created_at')
            ->get();

        return view('security.two-factor', compact('devices'));
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
        ]);

        $device = $twoFactor->createPendingDevice(auth()->user(), $validated['label']);

        session([
            AuthSession::PENDING_DEVICE_ID => $device->id,
            AuthSession::SETUP_FROM_ADMIN => true,
        ]);

        return redirect()
            ->route('two-factor.setup')
            ->with('success', 'Scan barcode di Google Authenticator, lalu masukkan kode 6 digit.');
    }

    public function destroy(TwoFactorDevice $device, TwoFactorService $twoFactor): RedirectResponse
    {
        abort_unless($device->user_id === auth()->id(), 403);

        if (auth()->user()->confirmedTwoFactorDevices()->count() <= 1 && $device->isConfirmed()) {
            return back()->with('error', 'Minimal satu perangkat authenticator harus tetap aktif.');
        }

        $label = $device->label;
        $device->delete();

        if (! auth()->user()->hasConfirmedTwoFactor()) {
            $twoFactor->resetForUser(auth()->user());
        }

        return back()->with('success', "Perangkat \"{$label}\" dihapus.");
    }
}
