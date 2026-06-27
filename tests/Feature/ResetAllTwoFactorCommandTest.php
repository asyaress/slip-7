<?php

namespace Tests\Feature;

use App\Models\TwoFactorDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ResetAllTwoFactorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_two_factor_state_for_all_accounts_with_two_factor_data(): void
    {
        $userWithTwoFactor = User::factory()->create([
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode(['ABCD-EFGH'])),
        ]);

        TwoFactorDevice::create([
            'user_id' => $userWithTwoFactor->id,
            'label' => 'HP Kantor',
            'secret' => Crypt::encryptString('SECRET123'),
            'confirmed_at' => now(),
        ]);

        TwoFactorDevice::create([
            'user_id' => $userWithTwoFactor->id,
            'label' => 'Cadangan',
            'secret' => Crypt::encryptString('SECRET456'),
        ]);

        $userWithoutTwoFactor = User::factory()->create();

        $this->artisan('security:reset-two-factor --force')
            ->expectsOutput('Reset 2FA selesai untuk 1 akun.')
            ->expectsOutput('Perangkat authenticator dihapus: 2.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('two_factor_devices', [
            'user_id' => $userWithTwoFactor->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userWithTwoFactor->id,
            'two_factor_enabled_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userWithoutTwoFactor->id,
        ]);
    }

    public function test_it_reports_when_no_two_factor_data_exists(): void
    {
        User::factory()->create();

        $this->artisan('security:reset-two-factor --force')
            ->expectsOutput('Tidak ada akun dengan data 2FA yang perlu di-reset.')
            ->assertExitCode(0);
    }
}
