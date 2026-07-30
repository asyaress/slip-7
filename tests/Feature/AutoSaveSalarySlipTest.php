<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\MonthlyTunjanganRate;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoSaveSalarySlipTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_auto_saves_salary_slip_and_returns_json(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'nomor' => 7,
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'jabatan' => 'Administrasi',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('slip.autosave'), [
                'employee_id' => $employee->id,
                'bulan' => 6,
                'tahun' => 2026,
                'gaji_pokok' => '4.500.000',
                'bonus' => '300.000',
                'jumlah_kehadiran' => 26,
                'hadir' => 24,
                'sakit_izin' => 1,
                'tidak_hadir' => 1,
                'tunj_harian_transport' => '10.000',
                'tunj_bulanan_transport' => '260.000',
                'tunj_mode_transport' => 'harian',
                'pot_angsuran' => '50.000',
                'pot_kasbon' => 0,
                'pot_lain_lain' => 0,
                'fasilitas' => ['bpjs'],
            ]);

        $response->assertOk()
            ->assertJson([
                'saved' => true,
                'was_created' => true,
            ])
            ->assertJsonStructure([
                'slip_id',
                'message',
                'review_url',
                'edit_url',
                'updated_at',
            ]);

        $slip = SalarySlip::firstOrFail();

        $this->assertSame($employee->id, $slip->employee_id);
        $this->assertEquals(4500000.0, (float) $slip->gaji_pokok);
        $this->assertEquals(300000.0, (float) $slip->bonus);
        $this->assertEquals(10000.0, (float) $slip->tunjangan['transport']);
        $this->assertEquals(260000.0, (float) $slip->tunjangan_bulanan['transport']);
        $this->assertSame('harian', $slip->tunjangan_modes['transport']);
        $this->assertEquals(4990000.0, (float) $slip->take_home_pay);
    }

    public function test_auto_save_does_not_overwrite_period_tunjangan_defaults(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'nomor' => 8,
            'name' => 'Dina',
            'email' => 'dina@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);

        MonthlyTunjanganRate::create([
            'bulan' => 6,
            'tahun' => 2026,
            'rates' => [
                'transport' => 260000,
                'kehadiran' => 260000,
            ],
        ]);

        $this->actingAs($user)
            ->postJson(route('slip.autosave'), [
                'employee_id' => $employee->id,
                'bulan' => 6,
                'tahun' => 2026,
                'gaji_pokok' => '4.500.000',
                'jumlah_kehadiran' => 26,
                'hadir' => 24,
                'sakit_izin' => 0,
                'tidak_hadir' => 2,
                'tunj_harian_transport' => '20.000',
                'tunj_bulanan_transport' => '520.000',
                'tunj_mode_transport' => 'harian',
                'pot_angsuran' => 0,
                'pot_kasbon' => 0,
                'pot_lain_lain' => 0,
            ])
            ->assertOk();

        $defaults = MonthlyTunjanganRate::where('bulan', 6)
            ->where('tahun', 2026)
            ->firstOrFail()
            ->rates;

        $this->assertSame(260000, $defaults['transport']);
        $this->assertSame(260000, $defaults['kehadiran']);
    }

    public function test_existing_fractional_daily_tunjangan_is_loaded_as_monthly_mode(): void
    {
        $employee = Employee::create([
            'nomor' => 9,
            'name' => 'Lia',
            'email' => 'lia@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);

        $slip = SalarySlip::create([
            'employee_id' => $employee->id,
            'bulan' => 6,
            'tahun' => 2026,
            'nomor_surat' => 'seed/9/6/2026',
            'gaji_pokok' => 4500000,
            'tunjangan' => ['transport' => 250000 / 26],
            'tunjangan_bulanan' => ['transport' => 250000],
            'potongan' => ['angsuran' => 0, 'kasbon' => 0, 'lain_lain' => 0],
            'fasilitas' => [],
            'lembur' => ['weeks' => [], 'total' => 0],
            'total_lembur' => 0,
            'jumlah_kehadiran' => 26,
            'hadir' => 24,
            'sakit_izin' => 0,
            'tidak_hadir' => 2,
            'total_tunjangan' => 250000 / 26,
            'total_potongan' => 0,
            'take_home_pay' => 4730769.230769,
            'total_fasilitas' => 0,
            'total_pendapatan' => 4730769.230769,
        ]);

        $formInputs = $slip->toFormInputs();

        $this->assertSame('bulanan', $formInputs['tunj_mode_transport']);
        $this->assertEquals(250000.0, (float) $formInputs['tunj_bulanan_transport']);
    }

    public function test_updating_one_tunjangan_does_not_change_other_tunjangan_values(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'nomor' => 10,
            'name' => 'Mira',
            'email' => 'mira@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);

        $payload = [
            'employee_id' => $employee->id,
            'bulan' => 6,
            'tahun' => 2026,
            'gaji_pokok' => '4.500.000',
            'jumlah_kehadiran' => 26,
            'hadir' => 24,
            'sakit_izin' => 0,
            'tidak_hadir' => 2,
            'tunj_harian_transport' => '10.000',
            'tunj_bulanan_transport' => '260.000',
            'tunj_mode_transport' => 'harian',
            'tunj_harian_kehadiran' => '10.000',
            'tunj_bulanan_kehadiran' => '260.000',
            'tunj_mode_kehadiran' => 'harian',
            'pot_angsuran' => 0,
            'pot_kasbon' => 0,
            'pot_lain_lain' => 0,
        ];

        $this->actingAs($user)
            ->postJson(route('slip.autosave'), $payload)
            ->assertOk();

        $payload['tunj_bulanan_transport'] = '500.000';
        $payload['tunj_mode_transport'] = 'bulanan';

        $this->actingAs($user)
            ->postJson(route('slip.autosave'), $payload)
            ->assertOk();

        $slip = SalarySlip::firstOrFail();

        $this->assertEquals(500000.0, (float) $slip->tunjangan_bulanan['transport']);
        $this->assertEqualsWithDelta(500000 / 26, (float) $slip->tunjangan['transport'], 0.001);
        $this->assertSame('bulanan', $slip->tunjangan_modes['transport']);
        $this->assertEquals(260000.0, (float) $slip->tunjangan_bulanan['kehadiran']);
        $this->assertEquals(10000.0, (float) $slip->tunjangan['kehadiran']);
    }

    public function test_monthly_tunjangan_is_fixed_when_attendance_inputs_change(): void
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'nomor' => 11,
            'name' => 'Nina',
            'email' => 'nina@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);

        $payload = [
            'employee_id' => $employee->id,
            'bulan' => 6,
            'tahun' => 2026,
            'gaji_pokok' => '4.500.000',
            'jumlah_kehadiran' => 26,
            'hadir' => 24,
            'sakit_izin' => 0,
            'tidak_hadir' => 2,
            'tunj_bulanan_transport' => '260.000',
            'tunj_mode_transport' => 'bulanan',
            'pot_angsuran' => 0,
            'pot_kasbon' => 0,
            'pot_lain_lain' => 0,
        ];

        $this->actingAs($user)
            ->postJson(route('slip.autosave'), $payload)
            ->assertOk();

        $firstThp = (float) SalarySlip::firstOrFail()->take_home_pay;

        $payload['jumlah_kehadiran'] = 24;
        $payload['hadir'] = 10;

        $this->actingAs($user)
            ->postJson(route('slip.autosave'), $payload)
            ->assertOk();

        $slip = SalarySlip::firstOrFail();

        $this->assertEquals(4760000.0, $firstThp);
        $this->assertEquals(4760000.0, (float) $slip->take_home_pay);
        $this->assertEquals(260000.0, (float) $slip->tunjangan_bulanan['transport']);
        $this->assertSame('bulanan', $slip->tunjangan_modes['transport']);
    }
}
