<?php

namespace Tests\Unit;

use App\Services\LemburWeekService;
use Tests\TestCase;

class LemburWeekServiceTest extends TestCase
{
    public function test_weeks_are_grouped_by_their_sunday_end_date(): void
    {
        $june = LemburWeekService::weeksForMonth(6, 2026);
        $july = LemburWeekService::weeksForMonth(7, 2026);
        $august = LemburWeekService::weeksForMonth(8, 2026);

        $this->assertSame([
            ['2026-06-01', '2026-06-07'],
            ['2026-06-08', '2026-06-14'],
            ['2026-06-15', '2026-06-21'],
            ['2026-06-22', '2026-06-28'],
        ], $this->dateRanges($june));

        $this->assertSame([
            ['2026-06-29', '2026-07-05'],
            ['2026-07-06', '2026-07-12'],
            ['2026-07-13', '2026-07-19'],
            ['2026-07-20', '2026-07-26'],
        ], $this->dateRanges($july));

        $this->assertSame(['2026-07-27', '2026-08-02'], [
            $august[0]['date_start'],
            $august[0]['date_end'],
        ]);
    }

    public function test_from_request_saves_week_dates_and_totals_overtime(): void
    {
        $lembur = LemburWeekService::fromRequest([
            ['nominal' => '100.000'],
            ['nominal' => '50.000'],
        ], 7, 2026);

        $this->assertCount(4, $lembur['weeks']);
        $this->assertSame('2026-06-29', $lembur['weeks'][0]['date_start']);
        $this->assertSame('2026-07-05', $lembur['weeks'][0]['date_end']);
        $this->assertEquals(150000.0, $lembur['total']);
    }

    private function dateRanges(array $weeks): array
    {
        return array_map(
            fn (array $week): array => [$week['date_start'], $week['date_end']],
            $weeks
        );
    }
}
