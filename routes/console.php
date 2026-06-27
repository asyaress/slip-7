<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('security:reset-two-factor {--force : Jalankan tanpa konfirmasi tambahan}', function (TwoFactorService $twoFactor) {
    $users = User::query()
        ->where(function ($query) {
            $query
                ->whereNotNull('two_factor_enabled_at')
                ->orWhereNotNull('two_factor_recovery_codes')
                ->orWhereHas('twoFactorDevices');
        })
        ->withCount('twoFactorDevices')
        ->orderBy('id')
        ->get();

    if ($users->isEmpty()) {
        $this->info('Tidak ada akun dengan data 2FA yang perlu di-reset.');

        return Command::SUCCESS;
    }

    $deviceCount = (int) $users->sum('two_factor_devices_count');

    if (! $this->option('force') && ! $this->confirm("Reset 2FA untuk {$users->count()} akun dan hapus {$deviceCount} perangkat authenticator?")) {
        $this->warn('Reset 2FA dibatalkan.');

        return Command::FAILURE;
    }

    $resetUsers = 0;
    $deletedDevices = 0;

    foreach ($users as $user) {
        $deletedDevices += $twoFactor->resetForUser($user);
        $resetUsers++;
    }

    $this->info("Reset 2FA selesai untuk {$resetUsers} akun.");
    $this->line("Perangkat authenticator dihapus: {$deletedDevices}.");

    return Command::SUCCESS;
})->purpose('Reset 2FA semua akun dan hapus seluruh perangkat authenticator');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
