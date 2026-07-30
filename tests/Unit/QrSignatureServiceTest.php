<?php

namespace Tests\Unit;

use App\Services\QrSignatureService;
use Tests\TestCase;

class QrSignatureServiceTest extends TestCase
{
    public function test_qr_payload_uses_the_selected_signatory_identity(): void
    {
        $slip = [
            'take_home_pay' => 4750000,
            'nomor_surat' => '001 / SK / HRD / TSG / VII / 2026',
            'employee' => [
                'name' => 'TEST USER',
                'jabatan' => 'Staff',
            ],
            'nama_bulan' => 'JULI',
            'tahun' => 2026,
        ];

        $payload = QrSignatureService::buildPayload(
            $slip,
            config('employees.approval_signatories.hr')
        );

        $this->assertStringContainsString('Ditandatangani: HR & Umum', $payload);
        $this->assertStringContainsString('Penanda tangan: Sri Wahyuni, S.Kom.', $payload);
        $this->assertStringNotContainsString('Ir. Labib Naufal Muttaqien', $payload);
    }
}
