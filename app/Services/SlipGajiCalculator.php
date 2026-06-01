<?php

namespace App\Services;

use Carbon\Carbon;

class SlipGajiCalculator
{
    public static function tunjanganKeys(): array
    {
        return array_keys(config('slip.tunjangan', []));
    }

    public static function fasilitasKeys(): array
    {
        return array_keys(config('slip.fasilitas', []));
    }

    public static function parseRupiah(mixed $value): float
    {
        if (is_numeric($value)) {
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

    public static function calculate(array $data): array
    {
        $gajiPokok = (float) ($data['gaji_pokok'] ?? 0);
        $jumlahKehadiran = max(1, (int) ($data['jumlah_kehadiran'] ?? 26));
        $hadir = (int) ($data['hadir'] ?? 0);
        $totalLembur = (float) ($data['total_lembur'] ?? ($data['lembur']['total'] ?? 0));

        $tunjanganBulanan = MonthlyTunjanganService::fromRequest($data);
        $tunjanganHarian = [];
        foreach (self::tunjanganKeys() as $key) {
            $tunjanganHarian[$key] = $tunjanganBulanan[$key] / $jumlahKehadiran;
        }

        $totalTunjanganHarian = array_sum($tunjanganHarian);
        $tunjanganEarned = $totalTunjanganHarian * $hadir;

        $potongan = [
            'angsuran' => (float) ($data['pot_angsuran'] ?? 0),
            'kasbon' => (float) ($data['pot_kasbon'] ?? 0),
            'lain_lain' => (float) ($data['pot_lain_lain'] ?? 0),
        ];
        $totalPotongan = array_sum($potongan);

        // THP = Gaji Pokok + (Total Tunjangan per hari × Hari Hadir) + Lembur - Potongan
        $takeHomePay = $gajiPokok + $tunjanganEarned + $totalLembur - $totalPotongan;

        $fasilitas = self::normalizeFasilitas($data['fasilitas'] ?? []);

        return [
            'tunjangan' => $tunjanganHarian,
            'tunjangan_bulanan' => $tunjanganBulanan,
            'total_tunjangan' => $totalTunjanganHarian,
            'tunjangan_earned' => $tunjanganEarned,
            'potongan' => $potongan,
            'total_potongan' => $totalPotongan,
            'take_home_pay' => $takeHomePay,
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

    public static function formatRupiah(float|int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
