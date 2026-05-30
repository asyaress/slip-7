<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class QrSignatureService
{
    public static function buildPayload(array $slip): string
    {
        $thp = SlipGajiCalculator::formatRupiah($slip['take_home_pay']);

        return implode("\n", [
            config('company.name'),
            'Surat Keterangan Gaji',
            'No: '.$slip['nomor_surat'],
            'Nama: '.$slip['employee']['name'],
            'Jabatan: '.$slip['employee']['jabatan'],
            'Periode: '.$slip['nama_bulan'].' '.$slip['tahun'],
            'THP: '.$thp,
            'Direktur: '.config('employees.director.name'),
        ]);
    }

    public static function generate(array $slip, ?int $slipId = null): ?string
    {
        if (! self::canRunProcesses()) {
            return null;
        }

        $logoPath = public_path('images/logo_m.png');
        $scriptPath = base_path('scripts/generate_qr_signature.py');

        if (! file_exists($logoPath) || ! file_exists($scriptPath)) {
            return null;
        }

        $filename = $slipId
            ? "signatures/slip-{$slipId}.svg"
            : 'signatures/preview-'.md5(self::buildPayload($slip)).'.svg';

        $outputPath = Storage::disk('public')->path($filename);

        $python = self::pythonBinary();
        if (! $python) {
            return null;
        }

        try {
            $result = Process::timeout(30)->run([
                $python,
                $scriptPath,
                self::buildPayload($slip),
                $logoPath,
                $outputPath,
            ]);
        } catch (\Throwable $e) {
            Log::warning('QR signature generation failed', [
                'message' => $e->getMessage(),
                'slip_id' => $slipId,
            ]);

            return null;
        }

        if (! $result->successful() || ! file_exists($outputPath)) {
            Log::warning('QR signature script failed', [
                'stderr' => $result->errorOutput(),
                'slip_id' => $slipId,
            ]);

            return null;
        }

        return $filename;
    }

    public static function url(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return asset('storage/'.$relativePath);
    }

    private static function canRunProcesses(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $disabled, true);
    }

    private static function pythonBinary(): ?string
    {
        if (! self::canRunProcesses()) {
            return null;
        }

        foreach (['python3', 'python', 'py'] as $bin) {
            try {
                $check = Process::run([$bin, '--version']);
                if ($check->successful()) {
                    return $bin;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
