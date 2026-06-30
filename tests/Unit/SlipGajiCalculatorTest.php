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
        $this->assertEqualsWithDelta(192307.6923, $result['take_home_pay'], 0.001);
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
        $this->assertEquals(200000.0, $result['take_home_pay']);
    }
}
