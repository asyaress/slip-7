<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class NipService
{
    public static function generate(
        int $nomor,
        string $tglMasuk,
        string $tglLahir,
    ): string {
        $masuk = Carbon::parse($tglMasuk);
        $lahir = Carbon::parse($tglLahir);

        return sprintf(
            'TSG15%s%s%s%s%02d',
            $masuk->format('Y'),
            $lahir->format('d'),
            $lahir->format('m'),
            $lahir->format('Y'),
            $nomor,
        );
    }

    public static function forEmployee(Employee $employee): ?string
    {
        if ($employee->nip) {
            return $employee->nip;
        }

        if (! $employee->tgl_lahir) {
            return null;
        }

        return self::generate(
            $employee->nomor,
            $employee->tgl_masuk->format('Y-m-d'),
            $employee->tgl_lahir->format('Y-m-d'),
        );
    }
}
