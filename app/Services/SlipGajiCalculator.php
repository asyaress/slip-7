<?php

namespace App\Services;

use Carbon\Carbon;

class SlipGajiCalculator
{
    public static function parseRupiah(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^\d]/', '', (string) $value);

        return $cleaned !== '' ? (float) $cleaned : 0;
    }

    public static function calculate(array $data): array
    {
        $gajiPokok = (float) ($data['gaji_pokok'] ?? 0);

        $tunjangan = [
            'transport' => (float) ($data['tunj_transport'] ?? 0),
            'kehadiran' => (float) ($data['tunj_kehadiran'] ?? 0),
            'kinerja' => (float) ($data['tunj_kinerja'] ?? 0),
            'jabatan' => (float) ($data['tunj_jabatan'] ?? 0),
            'perawatan' => (float) ($data['tunj_perawatan'] ?? 0),
            'operator' => (float) ($data['tunj_operator'] ?? 0),
            'konsumsi' => (float) ($data['tunj_konsumsi'] ?? 0),
        ];

        $potongan = [
            'angsuran' => (float) ($data['pot_angsuran'] ?? 0),
            'kasbon' => (float) ($data['pot_kasbon'] ?? 0),
        ];

        $totalTunjangan = array_sum($tunjangan);
        $totalPotongan = array_sum($potongan);

        // THP = Gaji Pokok + Total Tunjangan (per bulan) - Potongan
        $takeHomePay = $gajiPokok + $totalTunjangan - $totalPotongan;

        $bpjs = (float) ($data['bpjs_kesehatan'] ?? 0);
        $makan = (float) ($data['makan_siang_malam'] ?? 0);
        $pensiun = (float) ($data['pensiun'] ?? 0);
        $totalFasilitas = $bpjs + $makan + $pensiun;
        $totalPendapatan = $takeHomePay + $totalFasilitas;

        return [
            'tunjangan' => $tunjangan,
            'potongan' => $potongan,
            'total_tunjangan' => $totalTunjangan,
            'total_potongan' => $totalPotongan,
            'take_home_pay' => $takeHomePay,
            'total_fasilitas' => $totalFasilitas,
            'total_pendapatan' => $totalPendapatan,
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
