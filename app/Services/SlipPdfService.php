<?php

namespace App\Services;

use App\Models\SalarySlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SlipPdfService
{
    public static function generate(SalarySlip $slip, ?string $password = null): string
    {
        $slipData = $slip->toSlipArray();

        $pdf = Pdf::loadView('slip.pdf', [
            'slip' => $slipData,
            'images' => [
                'kop' => self::embedPublicImage('images/kop.png'),
                'qr' => self::embedStorageImage($slipData['qr_signature_path'] ?? null),
                'signatures' => self::embedSignatureImages($slipData['signatures'] ?? []),
            ],
        ]);

        $pdf->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Serif');

        if ($password !== null && $password !== '') {
            $pdf->setEncryption($password, self::ownerPassword(), ['print']);
        }

        return $pdf->output();
    }

    public static function generateForEmail(SalarySlip $slip): string
    {
        return self::generate($slip, self::emailPassword($slip));
    }

    public static function emailPassword(SalarySlip $slip): string
    {
        $slip->loadMissing('employee');
        $birthDate = $slip->employee?->tgl_lahir;

        if (! $birthDate) {
            throw new RuntimeException('Tanggal lahir karyawan belum diisi, PDF email tidak bisa diproteksi password.');
        }

        return $birthDate->format('dmY');
    }

    public static function filename(SalarySlip $slip): string
    {
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $slip->employee->name);
        $periode = str_pad((string) $slip->bulan, 2, '0', STR_PAD_LEFT).'-'.$slip->tahun;

        return "Slip-Gaji-{$name}-{$periode}.pdf";
    }

    private static function embedPublicImage(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        return self::embedFile($path);
    }

    private static function ownerPassword(): string
    {
        return substr(hash('sha256', (string) config('app.key')), 0, 32);
    }

    private static function embedStorageImage(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $path = Storage::disk('public')->path($relativePath);

        if (! is_file($path)) {
            return null;
        }

        if (str_ends_with(strtolower($relativePath), '.svg')) {
            $pngDataUri = self::svgFileToPngDataUri($path);
            if ($pngDataUri) {
                return $pngDataUri;
            }
        }

        return self::embedFile($path);
    }

    private static function embedSignatureImages(array $signatures): array
    {
        $images = [];

        foreach ($signatures as $key => $signature) {
            $images[$key] = self::embedStorageImage($signature['qr_signature_path'] ?? null);
        }

        return $images;
    }

    private static function svgFileToPngDataUri(string $svgPath): ?string
    {
        if (! extension_loaded('imagick') || ! class_exists(\Imagick::class)) {
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setBackgroundColor(new \ImagickPixel('white'));
            $imagick->readImageBlob((string) file_get_contents($svgPath));
            $imagick->setImageFormat('png');
            $imagick->resizeImage(280, 280, \Imagick::FILTER_LANCZOS, 1, true);

            return 'data:image/png;base64,'.base64_encode($imagick->getImageBlob());
        } catch (\Throwable) {
            return null;
        }
    }

    private static function embedFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
