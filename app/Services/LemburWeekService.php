<?php

namespace App\Services;

use Carbon\Carbon;

class LemburWeekService
{
    public const STATUS_BELUM_DIBAYAR = 'belum_dibayar';

    public const STATUS_SUDAH_DIBAYAR = 'sudah_dibayar';

    /**
     * Minggu kerja lapangan: Senin–Sabtu per minggu dalam bulan terpilih.
     *
     * @return array<int, array{minggu: int, periode: string, date_start: string, date_end: string}>
     */
    public static function weeksForMonth(int $bulan, int $tahun): array
    {
        $monthStart = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $weeks = [];
        $cursor = $monthStart->copy();

        while ($cursor->lte($monthEnd)) {
            if ($cursor->isMonday()) {
                $weekStart = $cursor->copy();
                $weekEnd = $cursor->copy()->addDays(5);

                if ($weekEnd->gt($monthEnd)) {
                    $weekEnd = $monthEnd->copy();
                }

                $monthLabel = $weekStart->locale('id')->translatedFormat('M');
                $periode = $weekStart->format('j').'–'.$weekEnd->format('j').' '.$monthLabel;

                $weeks[] = [
                    'minggu' => count($weeks) + 1,
                    'periode' => $periode,
                    'date_start' => $weekStart->format('Y-m-d'),
                    'date_end' => $weekEnd->format('Y-m-d'),
                ];
            }

            $cursor->addDay();
        }

        return $weeks;
    }

    public static function weeksForForm(int $bulan, int $tahun, ?array $savedLembur = null): array
    {
        $weeks = self::weeksForMonth($bulan, $tahun);
        $weekDataMap = self::weekDataFromSaved($savedLembur);
        $weekDataMap = array_replace_recursive($weekDataMap, self::weekDataFromOldInput());

        foreach ($weeks as &$week) {
            $data = $weekDataMap[$week['minggu']] ?? [];
            $week['nominal'] = $data['nominal'] ?? 0;
            $week['status'] = $data['status'] ?? self::STATUS_BELUM_DIBAYAR;
        }

        return $weeks;
    }

    public static function fromRequest(?array $lemburInput, int $bulan, int $tahun): array
    {
        $lemburInput = $lemburInput ?? [];
        $weeks = [];
        $total = 0;

        foreach (self::weeksForMonth($bulan, $tahun) as $index => $template) {
            $row = $lemburInput[$index] ?? [];
            $nominal = (float) SlipGajiCalculator::parseRupiah($row['nominal'] ?? 0);

            $weeks[] = [
                'minggu' => $template['minggu'],
                'periode' => $template['periode'],
                'nominal' => $nominal,
                'status' => self::normalizeStatus($row['status'] ?? null),
            ];

            $total += $nominal;
        }

        return [
            'weeks' => $weeks,
            'total' => $total,
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::normalizeStatus($status) === self::STATUS_SUDAH_DIBAYAR
            ? 'Sudah Dibayar'
            : 'Belum Dibayar';
    }

    public static function normalizeStatus(?string $status): string
    {
        return $status === self::STATUS_SUDAH_DIBAYAR
            ? self::STATUS_SUDAH_DIBAYAR
            : self::STATUS_BELUM_DIBAYAR;
    }

    /**
     * @return array<int, array{nominal: float, status: string}>
     */
    private static function weekDataFromSaved(?array $savedLembur): array
    {
        $map = [];

        if (! $savedLembur) {
            return $map;
        }

        foreach ($savedLembur['weeks'] ?? [] as $week) {
            if (isset($week['minggu'])) {
                $map[(int) $week['minggu']] = [
                    'nominal' => (float) ($week['nominal'] ?? 0),
                    'status' => self::normalizeStatus($week['status'] ?? null),
                ];
            }
        }

        return $map;
    }

    /**
     * @return array<int, array{nominal: float, status: string}>
     */
    private static function weekDataFromOldInput(): array
    {
        $map = [];
        $old = old('lembur');

        if (! is_array($old)) {
            return $map;
        }

        foreach ($old as $row) {
            if (! is_array($row) || ! isset($row['minggu'])) {
                continue;
            }

            $map[(int) $row['minggu']] = [
                'nominal' => (float) SlipGajiCalculator::parseRupiah($row['nominal'] ?? 0),
                'status' => self::normalizeStatus($row['status'] ?? null),
            ];
        }

        return $map;
    }
}
