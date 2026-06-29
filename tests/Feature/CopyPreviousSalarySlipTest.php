<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Models\User;
use App\Services\LemburWeekService;
use App\Services\SlipGajiCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyPreviousSalarySlipTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_slip_and_copy_previous_forms_are_not_nested(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)
            ->get(route('slip.create'))
            ->assertOk()
            ->getContent();

        $slipFormStart = strpos($content, '<form id="slip-form"');
        $slipFormEnd = strpos($content, '</form>', $slipFormStart);
        $copyFormStart = strpos($content, '<form id="copy-previous-form"');

        $this->assertNotFalse($slipFormStart);
        $this->assertNotFalse($slipFormEnd);
        $this->assertNotFalse($copyFormStart);
        $this->assertLessThan($slipFormEnd, $slipFormStart);
        $this->assertLessThan($copyFormStart, $slipFormEnd);
        $this->assertStringContainsString('form="copy-previous-form"', $content);
    }

    public function test_it_copies_previous_month_slips_and_updates_existing_targets(): void
    {
        $user = User::factory()->create();
        $employeeA = $this->createEmployee(1, 'Ayu');
        $employeeB = $this->createEmployee(2, 'Bima');

        $this->createSlip($employeeA, 5, 2026, [
            'gaji_pokok' => 5100000,
            'fasilitas' => ['bpjs', 'makan'],
            'lembur_nominals' => [50000, 75000, 100000, 125000],
            'take_home_pay' => 6120000,
            'total_pendapatan' => 6470000,
        ]);

        $sourceSlipB = $this->createSlip($employeeB, 5, 2026, [
            'gaji_pokok' => 4700000,
            'tunjangan' => ['transport' => 15000, 'kehadiran' => 10000],
            'tunjangan_bulanan' => ['transport' => 390000, 'kehadiran' => 260000, 'tempat_tinggal' => 200000],
            'potongan' => ['angsuran' => 200000, 'kasbon' => 50000, 'lain_lain' => 25000],
            'fasilitas' => ['bpjs'],
            'lembur_nominals' => [80000, 0, 50000, 25000],
            'take_home_pay' => 5400000,
            'total_pendapatan' => 5555000,
        ]);

        $existingTarget = $this->createSlip($employeeB, 6, 2026, [
            'gaji_pokok' => 1000000,
            'take_home_pay' => 1000000,
            'total_pendapatan' => 1000000,
            'fasilitas' => ['pensiun'],
            'lembur_nominals' => [0, 0, 0, 0, 0],
        ]);

        $response = $this->actingAs($user)->post(route('slip.copy-previous'), [
            'bulan' => 6,
            'tahun' => 2026,
        ]);

        $response->assertRedirect(route('slip.create', ['bulan' => 6, 'tahun' => 2026]));
        $response->assertSessionHas('success', function (?string $message): bool {
            return is_string($message)
                && str_contains($message, '1 slip dibuat')
                && str_contains($message, '1 slip diperbarui');
        });

        $this->assertDatabaseCount('salary_slips', 4);

        $copiedSlipA = SalarySlip::where('employee_id', $employeeA->id)
            ->where('bulan', 6)
            ->where('tahun', 2026)
            ->firstOrFail();

        $this->assertEquals(5100000.0, (float) $copiedSlipA->gaji_pokok);
        $this->assertSame(
            SlipGajiCalculator::nomorSurat($employeeA->nomor, 6, 2026),
            $copiedSlipA->nomor_surat
        );
        $this->assertSame(['bpjs', 'makan'], $copiedSlipA->fasilitas);

        $expectedJuneWeeks = LemburWeekService::weeksForMonth(6, 2026);
        $this->assertCount(count($expectedJuneWeeks), $copiedSlipA->lembur['weeks']);
        $this->assertSame($expectedJuneWeeks[0]['periode'], $copiedSlipA->lembur['weeks'][0]['periode']);
        $this->assertEquals(50000.0, (float) $copiedSlipA->lembur['weeks'][0]['nominal']);
        $this->assertEquals(0.0, (float) $copiedSlipA->lembur['weeks'][4]['nominal']);

        $existingTarget->refresh();
        $this->assertSame($existingTarget->id, SalarySlip::where('employee_id', $employeeB->id)->where('bulan', 6)->where('tahun', 2026)->firstOrFail()->id);
        $this->assertEquals((float) $sourceSlipB->gaji_pokok, (float) $existingTarget->gaji_pokok);
        $this->assertSame($sourceSlipB->potongan, $existingTarget->potongan);
        $this->assertSame($sourceSlipB->fasilitas, $existingTarget->fasilitas);
    }

    public function test_copied_slip_overtime_can_be_updated_from_the_salary_slip_form(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee(1, 'Ayu');

        $this->createSlip($employee, 5, 2026, [
            'gaji_pokok' => 5100000,
            'lembur_nominals' => [0, 0, 0, 0],
        ]);

        $this->actingAs($user)->post(route('slip.copy-previous'), [
            'bulan' => 6,
            'tahun' => 2026,
        ])->assertRedirect();

        $copiedSlip = SalarySlip::where('employee_id', $employee->id)
            ->where('bulan', 6)
            ->where('tahun', 2026)
            ->firstOrFail();

        $formData = $copiedSlip->toFormInputs();
        $formData['lembur'] = $copiedSlip->lembur['weeks'];
        $formData['lembur'][0]['nominal'] = '175.000';
        $formData['lembur'][0]['status'] = LemburWeekService::STATUS_SUDAH_DIBAYAR;

        $this->actingAs($user)
            ->post(route('slip.store'), $formData)
            ->assertRedirect(route('review.show', $copiedSlip));

        $copiedSlip->refresh();

        $this->assertEquals(175000.0, (float) $copiedSlip->lembur['weeks'][0]['nominal']);
        $this->assertSame(
            LemburWeekService::STATUS_SUDAH_DIBAYAR,
            $copiedSlip->lembur['weeks'][0]['status']
        );
        $this->assertEquals(175000.0, (float) $copiedSlip->total_lembur);
        $this->assertEquals(
            (float) $copiedSlip->take_home_pay + 175000.0,
            (float) $copiedSlip->total_pendapatan
        );
    }

    public function test_it_warns_when_previous_period_has_no_slips_to_copy(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('slip.copy-previous'), [
            'bulan' => 6,
            'tahun' => 2026,
        ]);

        $response->assertRedirect(route('slip.create', ['bulan' => 6, 'tahun' => 2026]));
        $response->assertSessionHas('warning', function (?string $message): bool {
            return is_string($message) && str_contains($message, 'Tidak ada slip');
        });

        $this->assertDatabaseCount('salary_slips', 0);
    }

    public function test_it_can_redirect_back_to_review_after_copying_previous_month_slips(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee(1, 'Ayu');

        $this->createSlip($employee, 5, 2026, [
            'gaji_pokok' => 5100000,
        ]);

        $response = $this->actingAs($user)->post(route('slip.copy-previous'), [
            'bulan' => 6,
            'tahun' => 2026,
            'redirect_to' => 'review',
        ]);

        $response->assertRedirect(route('review.index', ['bulan' => 6, 'tahun' => 2026]));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_slips', [
            'employee_id' => $employee->id,
            'bulan' => 6,
            'tahun' => 2026,
        ]);
    }

    private function createEmployee(int $nomor, string $name): Employee
    {
        return Employee::create([
            'nomor' => $nomor,
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'jabatan' => 'Operator',
            'alamat' => 'Samarinda',
            'tgl_masuk' => '2024-01-15',
            'is_active' => true,
        ]);
    }

    private function createSlip(Employee $employee, int $bulan, int $tahun, array $overrides = []): SalarySlip
    {
        $weeks = [];

        foreach (LemburWeekService::weeksForMonth($bulan, $tahun) as $index => $week) {
            $weeks[] = [
                'minggu' => $week['minggu'],
                'periode' => $week['periode'],
                'nominal' => (float) ($overrides['lembur_nominals'][$index] ?? 0),
                'status' => $overrides['lembur_statuses'][$index] ?? LemburWeekService::STATUS_BELUM_DIBAYAR,
            ];
        }

        $lembur = $overrides['lembur'] ?? [
            'weeks' => $weeks,
            'total' => array_sum(array_column($weeks, 'nominal')),
        ];

        $data = [
            'employee_id' => $employee->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nomor_surat' => $overrides['nomor_surat'] ?? "seed/{$employee->nomor}/{$bulan}/{$tahun}",
            'gaji_pokok' => $overrides['gaji_pokok'] ?? 4500000,
            'tunjangan' => $overrides['tunjangan'] ?? ['transport' => 20000],
            'tunjangan_bulanan' => $overrides['tunjangan_bulanan'] ?? ['transport' => 520000, 'tempat_tinggal' => 300000],
            'potongan' => $overrides['potongan'] ?? ['angsuran' => 100000, 'kasbon' => 0, 'lain_lain' => 0],
            'bpjs_kesehatan' => 0,
            'makan_siang_malam' => 0,
            'pensiun' => 0,
            'fasilitas' => $overrides['fasilitas'] ?? ['bpjs'],
            'lembur' => $lembur,
            'total_lembur' => $overrides['total_lembur'] ?? (float) $lembur['total'],
            'jumlah_kehadiran' => $overrides['jumlah_kehadiran'] ?? 26,
            'hadir' => $overrides['hadir'] ?? 26,
            'sakit_izin' => $overrides['sakit_izin'] ?? 0,
            'tidak_hadir' => $overrides['tidak_hadir'] ?? 0,
            'total_tunjangan' => $overrides['total_tunjangan'] ?? 20000,
            'total_potongan' => $overrides['total_potongan'] ?? 100000,
            'take_home_pay' => $overrides['take_home_pay'] ?? 5220000,
            'total_fasilitas' => $overrides['total_fasilitas'] ?? 0,
            'total_pendapatan' => $overrides['total_pendapatan'] ?? ((float) ($overrides['take_home_pay'] ?? 5220000) + (float) $lembur['total']),
        ];

        return SalarySlip::create($data);
    }
}
