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
        'lembur', 'total_lembur',
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
            'lembur' => 'array',
            'total_lembur' => 'float',
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

    public function resolvedTotalPendapatan(): float
    {
        return $this->take_home_pay + $this->total_fasilitas + ($this->total_lembur ?? 0);
    }

    public function isEmailSent(): bool
    {
        return $this->email_sent_at !== null && $this->email_status === 'sent';
    }

    public function toFormInputs(): array
    {
        $tunjangan = $this->tunjangan ?? [];
        $potongan = $this->potongan ?? [];

        return [
            'employee_id' => $this->employee_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'gaji_pokok' => $this->gaji_pokok,
            'tunj_transport' => $tunjangan['transport'] ?? 0,
            'tunj_kehadiran' => $tunjangan['kehadiran'] ?? 0,
            'tunj_kinerja' => $tunjangan['kinerja'] ?? 0,
            'tunj_jabatan' => $tunjangan['jabatan'] ?? 0,
            'tunj_perawatan' => $tunjangan['perawatan'] ?? 0,
            'tunj_operator' => $tunjangan['operator'] ?? 0,
            'tunj_konsumsi' => $tunjangan['konsumsi'] ?? 0,
            'pot_angsuran' => $potongan['angsuran'] ?? 0,
            'pot_kasbon' => $potongan['kasbon'] ?? 0,
            'pot_lain_lain' => $potongan['lain_lain'] ?? 0,
            'jumlah_kehadiran' => $this->jumlah_kehadiran,
            'hadir' => $this->hadir,
            'sakit_izin' => $this->sakit_izin,
            'tidak_hadir' => $this->tidak_hadir,
            'bpjs_kesehatan' => $this->bpjs_kesehatan,
            'makan_siang_malam' => $this->makan_siang_malam,
            'pensiun' => $this->pensiun,
            'lembur' => $this->lembur,
        ];
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
            'lembur' => $this->lembur ?? ['weeks' => [], 'total' => 0],
            'total_lembur' => $this->total_lembur ?? 0,
            'total_tunjangan' => $this->total_tunjangan,
            'total_potongan' => $this->total_potongan,
            'take_home_pay' => $this->take_home_pay,
            'total_fasilitas' => $this->total_fasilitas,
            'total_pendapatan' => $this->resolvedTotalPendapatan(),
            'signatory' => config('employees.signatory'),
            'email_sent_at' => $this->email_sent_at,
            'email_status' => $this->email_status,
        ];

        return \App\Services\SlipGajiBuilder::attachQrSignature($slip, $this->id);
    }
}
