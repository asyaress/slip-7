<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'employee_id', 'bulan', 'tahun', 'nomor_surat',
        'gaji_pokok', 'bonus', 'bonus_description', 'tunjangan', 'tunjangan_bulanan', 'tunjangan_modes', 'potongan',
        'bpjs_kesehatan', 'makan_siang_malam', 'pensiun', 'fasilitas',
        'lembur', 'total_lembur',
        'jumlah_kehadiran', 'hadir', 'sakit_izin', 'tidak_hadir',
        'total_tunjangan', 'total_potongan', 'take_home_pay',
        'total_fasilitas', 'total_pendapatan',
        'email_sent_at', 'email_status', 'email_error',
    ];

    protected function casts(): array
    {
        return [
            'tunjangan' => 'array',
            'tunjangan_bulanan' => 'array',
            'tunjangan_modes' => 'array',
            'potongan' => 'array',
            'fasilitas' => 'array',
            'gaji_pokok' => 'float',
            'bonus' => 'float',
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

    public function resolvedFasilitas(): array
    {
        if (! empty($this->fasilitas)) {
            return $this->fasilitas;
        }

        return \App\Services\SlipGajiCalculator::fasilitasFromLegacy(
            (float) $this->bpjs_kesehatan,
            (float) $this->makan_siang_malam,
            (float) $this->pensiun,
        );
    }

    public function resolvedTunjanganBulanan(): array
    {
        if (! empty($this->tunjangan_bulanan)) {
            return $this->tunjangan_bulanan;
        }

        $tunjangan = $this->tunjangan ?? [];
        $monthly = [];

        foreach (\App\Services\SlipGajiCalculator::tunjanganKeys() as $key) {
            $monthly[$key] = (float) ($tunjangan[$key] ?? 0);
        }

        return $monthly;
    }

    public function resolvedTunjanganHarian(): array
    {
        $tunjangan = $this->tunjangan ?? [];
        $daily = [];

        foreach (\App\Services\SlipGajiCalculator::tunjanganKeys() as $key) {
            $daily[$key] = (float) ($tunjangan[$key] ?? 0);
        }

        if (array_sum($daily) > 0) {
            return $daily;
        }

        $monthly = $this->resolvedTunjanganBulanan();
        $days = max(1, (int) $this->jumlah_kehadiran);

        foreach (\App\Services\SlipGajiCalculator::tunjanganKeys() as $key) {
            if (\App\Services\SlipGajiCalculator::isTunjanganBulananOnly($key)) {
                $daily[$key] = 0;

                continue;
            }

            $daily[$key] = ($monthly[$key] ?? 0) / $days;
        }

        return $daily;
    }

    public function isEmailSent(): bool
    {
        return $this->email_sent_at !== null && $this->email_status === 'sent';
    }

    public function isEmailFailed(): bool
    {
        if ($this->email_status === 'failed') {
            return true;
        }

        return $this->email_status !== null
            && str_starts_with($this->email_status, 'failed');
    }

    public function emailFailureMessage(): ?string
    {
        if (! empty($this->email_error)) {
            return $this->email_error;
        }

        if ($this->isEmailFailed() && str_contains((string) $this->email_status, ':')) {
            return trim(substr((string) $this->email_status, 7));
        }

        return null;
    }

    public function toFormInputs(): array
    {
        $potongan = $this->potongan ?? [];
        $tunjanganBulanan = $this->resolvedTunjanganBulanan();
        $tunjanganHarian = $this->resolvedTunjanganHarian();
        $storedModes = $this->tunjangan_modes ?? [];

        $inputs = [
            'employee_id' => $this->employee_id,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'gaji_pokok' => $this->gaji_pokok,
            'bonus' => $this->bonus ?? 0,
            'bonus_description' => $this->bonus_description,
            'pot_angsuran' => $potongan['angsuran'] ?? 0,
            'pot_kasbon' => $potongan['kasbon'] ?? 0,
            'pot_lain_lain' => $potongan['lain_lain'] ?? 0,
            'jumlah_kehadiran' => $this->jumlah_kehadiran,
            'hadir' => $this->hadir,
            'sakit_izin' => $this->sakit_izin,
            'tidak_hadir' => $this->tidak_hadir,
            'fasilitas' => $this->resolvedFasilitas(),
            'lembur' => $this->lembur,
        ];

        foreach ($tunjanganBulanan as $key => $value) {
            $inputs["tunj_bulanan_{$key}"] = $value;
        }

        foreach ($tunjanganHarian as $key => $value) {
            $inputs["tunj_harian_{$key}"] = $value;
        }

        $days = max(1, (int) $this->jumlah_kehadiran);

        foreach (\App\Services\SlipGajiCalculator::tunjanganKeys() as $key) {
            if (\App\Services\SlipGajiCalculator::isTunjanganBulananOnly($key)) {
                $inputs["tunj_mode_{$key}"] = 'bulanan';

                continue;
            }

            if (in_array($storedModes[$key] ?? null, ['harian', 'bulanan'], true)) {
                $inputs["tunj_mode_{$key}"] = $storedModes[$key];

                continue;
            }

            $harian = (float) ($tunjanganHarian[$key] ?? 0);
            $bulanan = (float) ($tunjanganBulanan[$key] ?? 0);
            $hasFractionalDailyRate = abs($harian - round($harian)) > 0.0001;
            $hasManualMonthlyTotal = $bulanan > 0 && abs($bulanan - ($harian * $days)) > 1;

            $inputs["tunj_mode_{$key}"] = $hasFractionalDailyRate || $hasManualMonthlyTotal
                ? 'bulanan'
                : 'harian';
        }

        return $inputs;
    }

    public function toSlipArray(): array
    {
        $employee = $this->employee;
        $jumlahKehadiran = max(1, (int) $this->jumlah_kehadiran);
        $tunjanganBulanan = $this->resolvedTunjanganBulanan();
        $tunjanganHarian = $this->resolvedTunjanganHarian();
        $tunjanganModes = $this->tunjangan_modes ?? [];
        $totalTunjanganHarian = 0;
        $tunjanganFlatBulanan = 0;

        foreach (\App\Services\SlipGajiCalculator::tunjanganKeys() as $key) {
            $mode = $tunjanganModes[$key] ?? null;

            if (\App\Services\SlipGajiCalculator::isTunjanganBulananOnly($key) || $mode === 'bulanan') {
                $tunjanganFlatBulanan += (float) ($tunjanganBulanan[$key] ?? 0);
            } else {
                $totalTunjanganHarian += (float) ($tunjanganHarian[$key] ?? 0);
            }
        }

        $tunjanganEarned = ($totalTunjanganHarian * (int) $this->hadir) + $tunjanganFlatBulanan;

        $totalLembur = (float) ($this->total_lembur ?? 0);
        $resolvedPay = \App\Services\SlipGajiCalculator::resolveThpAndPendapatan(
            (float) $this->take_home_pay,
            $totalLembur,
            (float) ($this->total_pendapatan ?? 0)
        );

        $slip = [
            'id' => $this->id,
            'employee' => [
                'id' => $employee->id,
                'nomor' => $employee->nomor,
                'nip' => \App\Services\NipService::forEmployee($employee),
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
            'bonus' => $this->bonus ?? 0,
            'bonus_description' => $this->bonus_description,
            'bonus_label' => \App\Services\SlipGajiCalculator::bonusLabel($this->bonus_description),
            'tunjangan' => $tunjanganHarian,
            'tunjangan_bulanan' => $tunjanganBulanan,
            'tunjangan_modes' => $tunjanganModes,
            'tunjangan_earned' => $tunjanganEarned,
            'potongan' => $this->potongan,
            'jumlah_kehadiran' => $this->jumlah_kehadiran,
            'hadir' => $this->hadir,
            'sakit_izin' => $this->sakit_izin,
            'tidak_hadir' => $this->tidak_hadir,
            'fasilitas' => $this->resolvedFasilitas(),
            'lembur' => $this->lembur ?? ['weeks' => [], 'total' => 0],
            'total_lembur' => $totalLembur,
            'total_tunjangan' => $this->total_tunjangan ?: $totalTunjanganHarian,
            'total_potongan' => $this->total_potongan,
            'take_home_pay' => $resolvedPay['take_home_pay'],
            'total_pendapatan' => $resolvedPay['total_pendapatan'],
            'signatory' => config('employees.signatory'),
            'email_sent_at' => $this->email_sent_at,
            'email_status' => $this->email_status,
        ];

        return \App\Services\SlipGajiBuilder::attachQrSignature($slip, $this->id);
    }
}
