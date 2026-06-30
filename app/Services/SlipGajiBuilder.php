<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalarySlip;
use Carbon\Carbon;

class SlipGajiBuilder
{
    public static function buildFromValidated(array $validated, Employee $employee): array
    {
        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];
        $lembur = $validated['lembur'] ?? ['weeks' => [], 'total' => 0];
        $totalLembur = (float) ($lembur['total'] ?? 0);

        $validated['lembur'] = $lembur;
        $validated['total_lembur'] = $totalLembur;

        $calculation = SlipGajiCalculator::calculate($validated);

        $namaBulan = Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F');

        return [
            'employee' => [
                'id' => $employee->id,
                'nomor' => $employee->nomor,
                'nip' => NipService::forEmployee($employee),
                'name' => $employee->name,
                'email' => $employee->email,
                'jabatan' => $employee->jabatan,
                'alamat' => $employee->alamat,
                'tgl_masuk' => $employee->tgl_masuk->format('Y-m-d'),
            ],
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nama_bulan' => strtoupper($namaBulan),
            'nomor_surat' => SlipGajiCalculator::nomorSurat($employee->nomor, $bulan, $tahun),
            'masa_kerja' => SlipGajiCalculator::masaKerja($employee->tgl_masuk->format('Y-m-d')),
            'tgl_masuk' => $employee->tgl_masuk->format('d-m-Y'),
            'tanggal_cetak' => Carbon::now()->locale('id')->translatedFormat('d F Y'),
            'gaji_pokok' => (float) $validated['gaji_pokok'],
            'tunjangan' => $calculation['tunjangan'],
            'tunjangan_bulanan' => $calculation['tunjangan_bulanan'],
            'tunjangan_modes' => $calculation['tunjangan_modes'],
            'tunjangan_earned' => $calculation['tunjangan_earned'],
            'potongan' => $calculation['potongan'],
            'jumlah_kehadiran' => (int) $validated['jumlah_kehadiran'],
            'hadir' => (int) $validated['hadir'],
            'sakit_izin' => (int) ($validated['sakit_izin'] ?? 0),
            'tidak_hadir' => (int) ($validated['tidak_hadir'] ?? 0),
            'fasilitas' => $calculation['fasilitas'],
            'lembur' => $lembur,
            'total_lembur' => $totalLembur,
            'total_tunjangan' => $calculation['total_tunjangan'],
            'total_potongan' => $calculation['total_potongan'],
            'take_home_pay' => $calculation['take_home_pay'],
            'total_pendapatan' => $calculation['total_pendapatan'],
            'signatory' => config('employees.signatory'),
        ];
    }

    public static function saveSlip(array $slip, Employee $employee): SalarySlip
    {
        $saved = SalarySlip::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'bulan' => $slip['bulan'],
                'tahun' => $slip['tahun'],
            ],
            [
                'nomor_surat' => $slip['nomor_surat'],
                'gaji_pokok' => $slip['gaji_pokok'],
                'tunjangan' => $slip['tunjangan'],
                'tunjangan_bulanan' => $slip['tunjangan_bulanan'] ?? null,
                'tunjangan_modes' => $slip['tunjangan_modes'] ?? null,
                'potongan' => $slip['potongan'],
                'bpjs_kesehatan' => 0,
                'makan_siang_malam' => 0,
                'pensiun' => 0,
                'fasilitas' => $slip['fasilitas'] ?? [],
                'lembur' => $slip['lembur'] ?? ['weeks' => [], 'total' => 0],
                'total_lembur' => $slip['total_lembur'] ?? 0,
                'jumlah_kehadiran' => $slip['jumlah_kehadiran'],
                'hadir' => $slip['hadir'],
                'sakit_izin' => $slip['sakit_izin'],
                'tidak_hadir' => $slip['tidak_hadir'],
                'total_tunjangan' => $slip['total_tunjangan'],
                'total_potongan' => $slip['total_potongan'],
                'take_home_pay' => $slip['take_home_pay'],
                'total_fasilitas' => 0,
                'total_pendapatan' => $slip['total_pendapatan'] ?? 0,
            ]
        );

        QrSignatureService::generate($slip, $saved->id);

        return $saved;
    }

    public static function copySlipToPeriod(SalarySlip $sourceSlip, int $bulan, int $tahun): SalarySlip
    {
        $sourceSlip->loadMissing('employee');
        $employee = $sourceSlip->employee;

        $formInputs = $sourceSlip->toFormInputs();
        $formInputs['employee_id'] = $employee->id;
        $formInputs['bulan'] = $bulan;
        $formInputs['tahun'] = $tahun;
        $formInputs['lembur'] = self::mapLemburToPeriod($sourceSlip, $bulan, $tahun);

        $slip = self::buildFromValidated($formInputs, $employee);

        return self::saveSlip($slip, $employee);
    }

    public static function attachQrSignature(array $slip, ?int $slipId = null): array
    {
        $qrPath = QrSignatureService::generate($slip, $slipId);
        $slip['qr_signature_url'] = QrSignatureService::url($qrPath);
        $slip['qr_signature_path'] = $qrPath;

        return $slip;
    }

    private static function mapLemburToPeriod(SalarySlip $sourceSlip, int $bulan, int $tahun): array
    {
        $weeks = array_map(function (array $week): array {
            return [
                'minggu' => (int) $week['minggu'],
                'periode' => $week['periode'],
                'nominal' => (float) ($week['nominal'] ?? 0),
                'status' => LemburWeekService::normalizeStatus($week['status'] ?? null),
            ];
        }, LemburWeekService::weeksForForm($bulan, $tahun, $sourceSlip->lembur));

        return [
            'weeks' => $weeks,
            'total' => array_sum(array_column($weeks, 'nominal')),
        ];
    }
}
