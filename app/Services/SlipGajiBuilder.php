<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalarySlip;
use Carbon\Carbon;

class SlipGajiBuilder
{
    public static function buildFromValidated(array $validated, Employee $employee): array
    {
        $calculation = SlipGajiCalculator::calculate($validated);
        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];
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
            'potongan' => $calculation['potongan'],
            'jumlah_kehadiran' => (int) $validated['jumlah_kehadiran'],
            'hadir' => (int) $validated['hadir'],
            'sakit_izin' => (int) ($validated['sakit_izin'] ?? 0),
            'tidak_hadir' => (int) ($validated['tidak_hadir'] ?? 0),
            'bpjs_kesehatan' => (float) ($validated['bpjs_kesehatan'] ?? 186222),
            'makan_siang_malam' => (float) ($validated['makan_siang_malam'] ?? 0),
            'pensiun' => (float) ($validated['pensiun'] ?? 0),
            'lembur' => $validated['lembur'] ?? ['weeks' => [], 'total' => 0],
            'total_lembur' => (float) ($validated['lembur']['total'] ?? 0),
            'total_tunjangan' => $calculation['total_tunjangan'],
            'total_potongan' => $calculation['total_potongan'],
            'take_home_pay' => $calculation['take_home_pay'],
            'total_fasilitas' => $calculation['total_fasilitas'],
            'total_pendapatan' => $calculation['total_pendapatan'] + (float) ($validated['lembur']['total'] ?? 0),
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
                'potongan' => $slip['potongan'],
                'bpjs_kesehatan' => $slip['bpjs_kesehatan'],
                'makan_siang_malam' => $slip['makan_siang_malam'],
                'pensiun' => $slip['pensiun'],
                'lembur' => $slip['lembur'] ?? ['weeks' => [], 'total' => 0],
                'total_lembur' => $slip['total_lembur'] ?? 0,
                'jumlah_kehadiran' => $slip['jumlah_kehadiran'],
                'hadir' => $slip['hadir'],
                'sakit_izin' => $slip['sakit_izin'],
                'tidak_hadir' => $slip['tidak_hadir'],
                'total_tunjangan' => $slip['total_tunjangan'],
                'total_potongan' => $slip['total_potongan'],
                'take_home_pay' => $slip['take_home_pay'],
                'total_fasilitas' => $slip['total_fasilitas'],
                'total_pendapatan' => $slip['total_pendapatan'],
            ]
        );

        QrSignatureService::generate($slip, $saved->id);

        return $saved;
    }

    public static function attachQrSignature(array $slip, ?int $slipId = null): array
    {
        $qrPath = QrSignatureService::generate($slip, $slipId);
        $slip['qr_signature_url'] = QrSignatureService::url($qrPath);
        $slip['qr_signature_path'] = $qrPath;

        return $slip;
    }
}
