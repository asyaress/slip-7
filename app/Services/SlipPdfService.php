<?php

namespace App\Services;

use App\Models\SalarySlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SlipPdfService
{
    public static function generate(SalarySlip $slip): string
    {
        $slipData = $slip->toSlipArray();

        $pdf = Pdf::loadView('slip.pdf', [
            'slip' => $slipData,
            'images' => [
                'kop' => self::embedPublicImage('images/kop.png'),
                'qr' => self::embedStorageImage($slipData['qr_signature_path'] ?? null),
            ],
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
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

    private static function embedStorageImage(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $path = Storage::disk('public')->path($relativePath);

        return self::embedFile($path);
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
