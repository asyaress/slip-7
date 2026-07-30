<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Services\SlipPdfService;
use RuntimeException;
use Tests\TestCase;

class SlipPdfServiceTest extends TestCase
{
    public function test_email_pdf_password_uses_employee_birth_date_ddmmyyyy(): void
    {
        $slip = new SalarySlip();
        $slip->setRelation('employee', new Employee([
            'tgl_lahir' => '1994-02-16',
        ]));

        $this->assertSame('16021994', SlipPdfService::emailPassword($slip));
    }

    public function test_email_pdf_password_requires_employee_birth_date(): void
    {
        $slip = new SalarySlip();
        $slip->setRelation('employee', new Employee());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tanggal lahir karyawan belum diisi');

        SlipPdfService::emailPassword($slip);
    }
}
