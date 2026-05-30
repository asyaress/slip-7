<?php

namespace App\Services;

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
        $payload = self::buildPayload($slip);

        $result = Process::timeout(30)->run([
            $python,
            $scriptPath,
            $payload,
            $logoPath,
            $outputPath,
        ]);

        if (! $result->successful() || ! file_exists($outputPath)) {
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

    private static function pythonBinary(): string
    {
        foreach (['python', 'python3', 'py'] as $bin) {
            $check = Process::run([$bin, '--version']);
            if ($check->successful()) {
                return $bin;
            }
        }

        return 'python';
    }
}
