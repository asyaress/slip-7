<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class QrSignatureService
{
    public static function buildPayload(array $slip, ?array $signatory = null): string
    {
        $thp = SlipGajiCalculator::formatRupiah($slip['take_home_pay']);
        $signatory ??= config('employees.approval_signatories.director', config('employees.director'));
        $signatoryTitle = trim((string) ($signatory['title'] ?? ''));
        $signatoryName = trim((string) ($signatory['name'] ?? ''));

        return implode("\n", [
            config('company.name'),
            'Surat Keterangan Gaji',
            'No: '.$slip['nomor_surat'],
            'Nama: '.$slip['employee']['name'],
            'Jabatan: '.$slip['employee']['jabatan'],
            'Periode: '.$slip['nama_bulan'].' '.$slip['tahun'],
            'THP: '.$thp,
            'Ditandatangani: '.$signatoryTitle,
            'Penanda tangan: '.$signatoryName,
        ]);
    }

    public static function generate(array $slip, ?int $slipId = null): ?string
    {
        $logoPath = public_path('images/logo_m.png');

        if (! file_exists($logoPath)) {
            Log::warning('QR logo missing', ['path' => $logoPath]);

            return null;
        }

        return self::generateForSignatory('director', $slip, $slipId);
    }

    public static function generateForSignatory(string $key, array $slip, ?int $slipId = null): ?string
    {
        $logoPath = public_path('images/logo_m.png');

        if (! file_exists($logoPath)) {
            Log::warning('QR logo missing', ['path' => $logoPath]);

            return null;
        }

        $signatory = self::signatories()[$key] ?? null;
        if (! $signatory) {
            return null;
        }

        $safeKey = preg_replace('/[^A-Za-z0-9_-]+/', '-', $key) ?: 'signatory';
        $payload = self::buildPayload($slip, $signatory);

        $filename = $slipId
            ? "signatures/slip-{$slipId}-{$safeKey}.svg"
            : 'signatures/preview-'.$safeKey.'-'.md5($payload).'.svg';

        $outputPath = Storage::disk('public')->path($filename);

        if (self::generateWithPhp($payload, $outputPath, $logoPath)) {
            return $filename;
        }

        if (self::generateWithPython($payload, $outputPath, $logoPath, $slipId)) {
            return $filename;
        }

        return null;
    }

    public static function generateAll(array $slip, ?int $slipId = null): array
    {
        $paths = [];

        foreach (array_keys(self::signatories()) as $key) {
            $paths[$key] = self::generateForSignatory($key, $slip, $slipId);
        }

        return $paths;
    }

    public static function signatories(): array
    {
        return config('employees.approval_signatories', [
            'director' => config('employees.director'),
        ]);
    }

    public static function url(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return asset('storage/'.$relativePath);
    }

    private static function generateWithPhp(string $payload, string $outputPath, string $logoPath): bool
    {
        try {
            $writer = new Writer(
                new ImageRenderer(
                    new RendererStyle(280, 1),
                    new SvgImageBackEnd
                )
            );

            $svg = $writer->writeString($payload, 'UTF-8', ErrorCorrectionLevel::H());
            $svg = self::embedLogoInSvg($svg, $logoPath);

            Storage::disk('public')->makeDirectory('signatures');
            file_put_contents($outputPath, $svg);

            return file_exists($outputPath);
        } catch (\Throwable $e) {
            Log::warning('PHP QR generation failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private static function embedLogoInSvg(string $svg, string $logoPath): string
    {
        $logoBytes = file_get_contents($logoPath);
        if ($logoBytes === false) {
            return $svg;
        }

        $logoB64 = base64_encode($logoBytes);
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'png' : $ext;

        if (! preg_match('/viewBox="0 0 (\d+) (\d+)"/', $svg, $match)
            && ! preg_match('/width="(\d+)" height="(\d+)"/', $svg, $match)) {
            return $svg;
        }

        $width = (int) $match[1];
        $height = (int) $match[2];
        $logoSize = (int) (min($width, $height) * 0.22);
        $x = (int) (($width - $logoSize) / 2);
        $y = (int) (($height - $logoSize) / 2);
        $pad = 4;

        $overlay = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="#ffffff" rx="2"/>'.
            '<image x="%d" y="%d" width="%d" height="%d" href="data:image/%s;base64,%s" preserveAspectRatio="xMidYMid meet"/>',
            $x - $pad,
            $y - $pad,
            $logoSize + ($pad * 2),
            $logoSize + ($pad * 2),
            $x,
            $y,
            $logoSize,
            $logoSize,
            $mime,
            $logoB64
        );

        return str_replace('</svg>', $overlay.'</svg>', $svg);
    }

    private static function generateWithPython(string $payload, string $outputPath, string $logoPath, ?int $slipId): bool
    {
        if (! self::canRunProcesses()) {
            return false;
        }

        $scriptPath = base_path('scripts/generate_qr_signature.py');
        if (! file_exists($scriptPath)) {
            return false;
        }

        $python = self::pythonBinary();
        if (! $python) {
            return false;
        }

        try {
            $result = Process::timeout(30)->run([
                $python,
                $scriptPath,
                $payload,
                $logoPath,
                $outputPath,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Python QR generation failed', [
                'message' => $e->getMessage(),
                'slip_id' => $slipId,
            ]);

            return false;
        }

        if (! $result->successful() || ! file_exists($outputPath)) {
            Log::warning('Python QR script failed', [
                'stderr' => $result->errorOutput(),
                'slip_id' => $slipId,
            ]);

            return false;
        }

        return true;
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
