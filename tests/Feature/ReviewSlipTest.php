<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSlipTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_page_hides_resigned_employees(): void
    {
        $user = User::factory()->create();
        $activeEmployee = $this->createEmployee(1, 'Ayu Aktif', true);
        $resignedEmployee = $this->createEmployee(2, 'Bima Resigned', false);

        $this->createSlip($activeEmployee, 7, 2026, 4500000);
        $this->createSlip($resignedEmployee, 7, 2026, 4700000);

        $this->actingAs($user)
            ->get(route('review.index', ['bulan' => 7, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('Ayu Aktif')
            ->assertDontSee('Bima Resigned')
            ->assertSee('Rp 4.500.000')
            ->assertDontSee('Rp 4.700.000');
    }

    private function createEmployee(int $nomor, string $name, bool $isActive): Employee
    {
        return Employee::create([
            'nomor' => $nomor,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => $isActive,
        ]);
    }

    private function createSlip(Employee $employee, int $bulan, int $tahun, float $takeHomePay): SalarySlip
    {
        return SalarySlip::create([
            'employee_id' => $employee->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nomor_surat' => "seed/{$employee->nomor}/{$bulan}/{$tahun}",
            'gaji_pokok' => $takeHomePay,
            'bonus' => 0,
            'bonus_description' => null,
            'tunjangan' => [],
            'tunjangan_bulanan' => [],
            'tunjangan_modes' => [],
            'potongan' => ['angsuran' => 0, 'kasbon' => 0, 'lain_lain' => 0],
            'fasilitas' => [],
            'lembur' => ['weeks' => [], 'total' => 0],
            'total_lembur' => 0,
            'jumlah_kehadiran' => 26,
            'hadir' => 26,
            'sakit_izin' => 0,
            'tidak_hadir' => 0,
            'total_tunjangan' => 0,
            'total_potongan' => 0,
            'take_home_pay' => $takeHomePay,
            'total_fasilitas' => 0,
            'total_pendapatan' => $takeHomePay,
        ]);
    }
}
