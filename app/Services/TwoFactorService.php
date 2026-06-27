<?php

namespace App\Services;

use App\Models\TwoFactorDevice;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private Google2FA $google2fa) {}

    public function createPendingDevice(User $user, string $label): TwoFactorDevice
    {
        return $user->twoFactorDevices()->create([
            'label' => $label,
            'secret' => Crypt::encryptString($this->google2fa->generateSecretKey()),
        ]);
    }

    public function qrCodeSvg(User $user, TwoFactorDevice $device): string
    {
        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('company.short_name', config('app.name')),
            $user->email,
            $this->decryptSecret($device)
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(240, 0),
                new SvgImageBackEnd
            )
        );

        return $writer->writeString($otpUrl);
    }

    public function manualKey(TwoFactorDevice $device): string
    {
        return $this->decryptSecret($device);
    }

    public function verifyDevice(TwoFactorDevice $device, string $code): bool
    {
        if (! $device->isConfirmed()) {
            return $this->google2fa->verifyKey($this->decryptSecret($device), $code);
        }

        $valid = $this->google2fa->verifyKey($this->decryptSecret($device), $code);

        if ($valid) {
            $device->update(['last_used_at' => now()]);
        }

        return $valid;
    }

    public function verifyForUser(User $user, string $code): bool
    {
        foreach ($user->confirmedTwoFactorDevices as $device) {
            if ($this->verifyDevice($device, $code)) {
                return true;
            }
        }

        return false;
    }

    public function confirmDevice(TwoFactorDevice $device): void
    {
        $device->update(['confirmed_at' => now()]);

        if (! $device->user->two_factor_enabled_at) {
            $device->user->update(['two_factor_enabled_at' => now()]);
        }
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = collect(range(1, 8))
            ->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4)))
            ->all();

        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ]);

        return $codes;
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->recoveryCodes($user);
        $normalized = strtoupper(str_replace(' ', '', trim($code)));

        $index = collect($codes)->search(
            fn ($stored) => strtoupper(str_replace(' ', '', $stored)) === $normalized
        );

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $codes = array_values($codes);

        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ]);

        return true;
    }

    public function recoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [];
    }

    public function resetForUser(User $user): int
    {
        $deletedDevices = $user->twoFactorDevices()->delete();

        $user->forceFill([
            'two_factor_enabled_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return $deletedDevices;
    }

    private function decryptSecret(TwoFactorDevice $device): string
    {
        return Crypt::decryptString($device->secret);
    }
}
