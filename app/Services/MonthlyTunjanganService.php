<?php

namespace App\Services;

use App\Models\MonthlyTunjanganRate;

class MonthlyTunjanganService
{
    public static function keys(): array
    {
        return array_keys(config('slip.tunjangan', []));
    }

    public static function emptyRates(): array
    {
        return array_fill_keys(self::keys(), 0.0);
    }

    public static function forPeriod(int $bulan, int $tahun): array
    {
        $record = MonthlyTunjanganRate::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        return array_merge(self::emptyRates(), $record?->rates ?? []);
    }

    public static function saveForPeriod(int $bulan, int $tahun, array $rates): array
    {
        $normalized = self::emptyRates();

        foreach (self::keys() as $key) {
            $normalized[$key] = (float) ($rates[$key] ?? 0);
        }

        MonthlyTunjanganRate::updateOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun],
            ['rates' => $normalized]
        );

        return $normalized;
    }

    public static function toFormFields(array $rates): array
    {
        $fields = [];

        foreach (self::keys() as $key) {
            $fields["tunj_bulanan_{$key}"] = $rates[$key] ?? 0;
        }

        return $fields;
    }

    public static function fromRequest(array $input): array
    {
        $rates = self::emptyRates();

        foreach (self::keys() as $key) {
            $field = "tunj_bulanan_{$key}";
            if (array_key_exists($field, $input)) {
                $rates[$key] = SlipGajiCalculator::parseRupiah($input[$field]);
            } elseif (isset($input['tunjangan_bulanan'][$key])) {
                $rates[$key] = (float) $input['tunjangan_bulanan'][$key];
            }
        }

        return $rates;
    }
}
