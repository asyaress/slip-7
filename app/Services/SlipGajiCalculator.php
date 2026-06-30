<?php

namespace App\Services;

use Carbon\Carbon;

class SlipGajiCalculator
{
    public static function tunjanganKeys(): array
    {
        return array_keys(config('slip.tunjangan', []));
    }

    public static function tunjanganBulananOnlyKeys(): array
    {
        return config('slip.tunjangan_bulanan_only', []);
    }

    public static function isTunjanganBulananOnly(string $key): bool
    {
        return in_array($key, self::tunjanganBulananOnlyKeys(), true);
    }

    public static function fasilitasKeys(): array
    {
        return array_keys(config('slip.fasilitas', []));
    }

    public static function parseRupiah(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^\d]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    public static function normalizeFasilitas(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $allowed = self::fasilitasKeys();

        return array_values(array_unique(array_filter(
            $input,
            fn ($key) => in_array($key, $allowed, true)
        )));
    }

    public static function fasilitasFromLegacy(float $bpjs, float $makan, float $pensiun): array
    {
        $selected = [];

        if ($bpjs > 0) {
            $selected[] = 'bpjs';
        }
        if ($makan > 0) {
            $selected[] = 'makan';
        }
        if ($pensiun > 0) {
            $selected[] = 'pensiun';
        }

        return $selected;
    }

    /**
     * @return array{harian: array<string, float>, bulanan: array<string, float>, modes: array<string, string>}
     */
    public static function resolveTunjanganFromRequest(array $data, int $jumlahKehadiran): array
    {
        $days = max(1, $jumlahKehadiran);
        $harian = [];
        $bulanan = [];
        $modes = [];

        foreach (self::tunjanganKeys() as $key) {
            if (self::isTunjanganBulananOnly($key)) {
                $b = self::parseRupiah($data["tunj_bulanan_{$key}"] ?? null);
                $bulanan[$key] = $b;
                $harian[$key] = 0;
                $modes[$key] = 'bulanan';

                continue;
            }

            $h = self::parseRupiah($data["tunj_harian_{$key}"] ?? null);
            $b = self::parseRupiah($data["tunj_bulanan_{$key}"] ?? null);
            $mode = $data["tunj_mode_{$key}"] ?? null;
            $hasHarian = array_key_exists("tunj_harian_{$key}", $data);
            $hasBulanan = array_key_exists("tunj_bulanan_{$key}", $data);
            $autoBulanan = $h * $days;

            if ($mode === 'bulanan') {
                $bulanan[$key] = $b;
                $harian[$key] = $b / $days;
                $modes[$key] = 'bulanan';
            } elseif ($mode === 'harian') {
                $harian[$key] = $h;
                $bulanan[$key] = $autoBulanan;
                $modes[$key] = 'harian';
            } elseif ($hasBulanan && $hasHarian && $b > 0 && abs($b - $autoBulanan) > 1) {
                $bulanan[$key] = $b;
                $harian[$key] = $b / $days;
                $modes[$key] = 'bulanan';
            } elseif ($hasHarian) {
                $harian[$key] = $h;
                $bulanan[$key] = $hasBulanan ? $b : $autoBulanan;
                $modes[$key] = 'harian';
            } elseif ($hasBulanan && $b > 0) {
                $bulanan[$key] = $b;
                $harian[$key] = $b / $days;
                $modes[$key] = 'bulanan';
            } else {
                $harian[$key] = 0;
                $bulanan[$key] = 0;
                $modes[$key] = 'harian';
            }
        }

        return compact('harian', 'bulanan', 'modes');
    }

    public static function calculate(array $data): array
    {
        $gajiPokok = (float) ($data['gaji_pokok'] ?? 0);
        $jumlahKehadiran = max(1, (int) ($data['jumlah_kehadiran'] ?? 26));
        $hadir = (int) ($data['hadir'] ?? 0);
        $totalLembur = (float) ($data['total_lembur'] ?? ($data['lembur']['total'] ?? 0));

        $tunjanganResolved = self::resolveTunjanganFromRequest($data, $jumlahKehadiran);
        $tunjanganHarian = $tunjanganResolved['harian'];
        $tunjanganBulanan = $tunjanganResolved['bulanan'];
        $tunjanganModes = $tunjanganResolved['modes'];

        $totalTunjanganHarian = 0;
        $tunjanganFlatBulanan = 0;

        foreach (self::tunjanganKeys() as $key) {
            if (self::isTunjanganBulananOnly($key) || ($tunjanganModes[$key] ?? null) === 'bulanan') {
                $tunjanganFlatBulanan += (float) ($tunjanganBulanan[$key] ?? 0);
            } else {
                $totalTunjanganHarian += (float) ($tunjanganHarian[$key] ?? 0);
            }
        }

        $tunjanganEarned = ($totalTunjanganHarian * $hadir) + $tunjanganFlatBulanan;

        $potongan = [
            'angsuran' => (float) ($data['pot_angsuran'] ?? 0),
            'kasbon' => (float) ($data['pot_kasbon'] ?? 0),
            'lain_lain' => (float) ($data['pot_lain_lain'] ?? 0),
        ];
        $totalPotongan = array_sum($potongan);

        // THP = Gaji Pokok + (Tunjangan harian x Hadir) + Tunjangan bulanan fixed - Potongan.
        $takeHomePay = $gajiPokok + $tunjanganEarned - $totalPotongan;
        $totalPendapatan = $takeHomePay + $totalLembur;

        $fasilitas = self::normalizeFasilitas($data['fasilitas'] ?? []);

        return [
            'tunjangan' => $tunjanganHarian,
            'tunjangan_bulanan' => $tunjanganBulanan,
            'tunjangan_modes' => $tunjanganModes,
            'total_tunjangan' => $totalTunjanganHarian,
            'tunjangan_earned' => $tunjanganEarned,
            'potongan' => $potongan,
            'total_potongan' => $totalPotongan,
            'take_home_pay' => $takeHomePay,
            'total_pendapatan' => $totalPendapatan,
            'fasilitas' => $fasilitas,
        ];
    }

    public static function masaKerja(string $tglMasuk): string
    {
        $start = Carbon::parse($tglMasuk);
        $now = Carbon::now();

        $years = (int) $start->diffInYears($now);
        $months = (int) $start->copy()->addYears($years)->diffInMonths($now);
        $days = (int) $start->copy()->addYears($years)->addMonths($months)->diffInDays($now);

        return "{$years} Tahun {$months} Bulan {$days} Hari";
    }

    public static function nomorSurat(int $employeeNomor, int $bulan, int $tahun): string
    {
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return sprintf(
            '%d / SK / HRD / %s / %s / %d',
            $employeeNomor,
            config('company.nomor_surat_prefix', 'TSG'),
            $romawi[$bulan - 1] ?? 'I',
            $tahun
        );
    }

    /**
     * @return array<string, array{label: string, amount: float}>
     */
    public static function resolveActiveTunjanganBulanan(array $slip): array
    {
        $active = [];
        $tunjBulanan = $slip['tunjangan_bulanan'] ?? [];
        $jumlahHariKerja = max(1, (int) ($slip['jumlah_kehadiran'] ?? 26));

        foreach (config('slip.tunjangan', []) as $key => $label) {
            $bulanan = (float) ($tunjBulanan[$key] ?? 0);

            if ($bulanan <= 0 && ! self::isTunjanganBulananOnly($key)) {
                $harian = (float) ($slip['tunjangan'][$key] ?? 0);
                if ($harian > 0) {
                    $bulanan = $harian * $jumlahHariKerja;
                }
            }

            if ($bulanan > 0) {
                $active[$key] = [
                    'label' => $label,
                    'amount' => $bulanan,
                ];
            }
        }

        return $active;
    }

    /**
     * @return array{take_home_pay: float, total_pendapatan: float}
     */
    public static function resolveThpAndPendapatan(float $storedThp, float $totalLembur, float $storedPendapatan): array
    {
        if ($storedPendapatan > 0) {
            return [
                'take_home_pay' => $storedThp,
                'total_pendapatan' => $storedPendapatan,
            ];
        }

        // Slip lama: take_home_pay sudah termasuk lembur
        return [
            'take_home_pay' => max(0, $storedThp - $totalLembur),
            'total_pendapatan' => $storedThp,
        ];
    }

    public static function formatRupiah(float|int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
