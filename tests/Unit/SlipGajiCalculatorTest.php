<?php

namespace Tests\Unit;

use App\Services\SlipGajiCalculator;
use Tests\TestCase;

class SlipGajiCalculatorTest extends TestCase
{
    public function test_monthly_tunjangan_mode_preserves_manual_monthly_total(): void
    {
        $result = SlipGajiCalculator::calculate([
            'gaji_pokok' => 0,
            'jumlah_kehadiran' => 26,
            'hadir' => 20,
            'tunj_harian_transport' => 10000,
            'tunj_bulanan_transport' => 250000,
            'tunj_mode_transport' => 'bulanan',
        ]);

        $this->assertEquals(250000.0, $result['tunjangan_bulanan']['transport']);
        $this->assertEqualsWithDelta(9615.3846, $result['tunjangan']['transport'], 0.001);
        $this->assertSame('bulanan', $result['tunjangan_modes']['transport']);
        $this->assertEquals(250000.0, $result['take_home_pay']);
    }

    public function test_daily_tunjangan_mode_recalculates_monthly_total_from_daily_rate(): void
    {
        $result = SlipGajiCalculator::calculate([
            'gaji_pokok' => 0,
            'jumlah_kehadiran' => 26,
            'hadir' => 20,
            'tunj_harian_transport' => 10000,
            'tunj_bulanan_transport' => 250000,
            'tunj_mode_transport' => 'harian',
        ]);

        $this->assertEquals(10000.0, $result['tunjangan']['transport']);
        $this->assertEquals(260000.0, $result['tunjangan_bulanan']['transport']);
        $this->assertSame('harian', $result['tunjangan_modes']['transport']);
        $this->assertEquals(200000.0, $result['take_home_pay']);
    }

    public function test_monthly_tunjangan_mode_is_not_prorated_by_attendance(): void
    {
        $first = SlipGajiCalculator::calculate([
            'gaji_pokok' => 0,
            'jumlah_kehadiran' => 26,
            'hadir' => 20,
            'tunj_bulanan_transport' => 260000,
            'tunj_mode_transport' => 'bulanan',
        ]);

        $second = SlipGajiCalculator::calculate([
            'gaji_pokok' => 0,
            'jumlah_kehadiran' => 24,
            'hadir' => 10,
            'tunj_bulanan_transport' => 260000,
            'tunj_mode_transport' => 'bulanan',
        ]);

        $this->assertEquals(260000.0, $first['take_home_pay']);
        $this->assertEquals(260000.0, $second['take_home_pay']);
    }

    public function test_bonus_is_added_to_take_home_pay_before_overtime_total(): void
    {
        $result = SlipGajiCalculator::calculate([
            'gaji_pokok' => 4500000,
            'bonus' => 300000,
            'jumlah_kehadiran' => 26,
            'hadir' => 24,
            'tunj_harian_transport' => 10000,
            'tunj_bulanan_transport' => 260000,
            'tunj_mode_transport' => 'harian',
            'pot_angsuran' => 50000,
            'total_lembur' => 125000,
        ]);

        $this->assertEquals(4990000.0, $result['take_home_pay']);
        $this->assertEquals(5115000.0, $result['total_pendapatan']);
    }
}
