<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'employee_id', 'bulan', 'tahun', 'nomor_surat',
        'gaji_pokok', 'tunjangan', 'potongan',
        'bpjs_kesehatan', 'makan_siang_malam', 'pensiun',
        'jumlah_kehadiran', 'hadir', 'sakit_izin', 'tidak_hadir',
        'total_tunjangan', 'total_potongan', 'take_home_pay',
        'total_fasilitas', 'total_pendapatan',
        'email_sent_at', 'email_status',
    ];

    protected function casts(): array
    {
        return [
            'tunjangan' => 'array',
            'potongan' => 'array',
            'gaji_pokok' => 'float',
            'bpjs_kesehatan' => 'float',
            'makan_siang_malam' => 'float',
            'pensiun' => 'float',
            'total_tunjangan' => 'float',
            'total_potongan' => 'float',
            'take_home_pay' => 'float',
            'total_fasilitas' => 'float',
            'total_pendapatan' => 'float',
            'email_sent_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function namaBulan(): string
    {
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $bulan[$this->bulan] ?? '';
    }

    public function periodeLabel(): string
    {
        return strtoupper($this->namaBulan()).' '.$this->tahun;
    }

    public function isEmailSent(): bool
    {
        return $this->email_sent_at !== null && $this->email_status === 'sent';
    }

    public function toSlipArray(): array
    {
        $employee = $this->employee;

        $slip = [
            'id' => $this->id,
            'employee' => [
                'id' => $employee->id,
                'nomor' => $employee->nomor,
                'name' => $employee->name,
                'email' => $employee->email,
                'jabatan' => $employee->jabatan,
                'alamat' => $employee->alamat,
                'tgl_masuk' => $employee->tgl_masuk->format('Y-m-d'),
            ],
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'nama_bulan' => strtoupper($this->namaBulan()),
            'nomor_surat' => $this->nomor_surat,
            'masa_kerja' => \App\Services\SlipGajiCalculator::masaKerja($employee->tgl_masuk->format('Y-m-d')),
            'tgl_masuk' => $employee->tgl_masuk->format('d-m-Y'),
            'tanggal_cetak' => $this->updated_at->locale('id')->translatedFormat('d F Y'),
            'gaji_pokok' => $this->gaji_pokok,
            'tunjangan' => $this->tunjangan,
            'potongan' => $this->potongan,
            'jumlah_kehadiran' => $this->jumlah_kehadiran,
            'hadir' => $this->hadir,
            'sakit_izin' => $this->sakit_izin,
            'tidak_hadir' => $this->tidak_hadir,
            'bpjs_kesehatan' => $this->bpjs_kesehatan,
            'makan_siang_malam' => $this->makan_siang_malam,
            'pensiun' => $this->pensiun,
            'total_tunjangan' => $this->total_tunjangan,
            'total_potongan' => $this->total_potongan,
            'take_home_pay' => $this->take_home_pay,
            'total_fasilitas' => $this->total_fasilitas,
            'total_pendapatan' => $this->total_pendapatan,
            'signatory' => config('employees.signatory'),
            'email_sent_at' => $this->email_sent_at,
            'email_status' => $this->email_status,
        ];

        return \App\Services\SlipGajiBuilder::attachQrSignature($slip, $this->id);
    }
}
