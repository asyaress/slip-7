<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'nomor', 'nip', 'name', 'email', 'jabatan', 'alamat',
        'tgl_lahir', 'jenis_kelamin', 'tgl_masuk', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
            'tgl_masuk' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isResigned(): bool
    {
        return ! $this->is_active;
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Aktif' : 'Resigned';
    }

    public function masaKerja(): string
    {
        return \App\Services\SlipGajiCalculator::masaKerja($this->tgl_masuk->format('Y-m-d'));
    }

    public function resolvedNip(): ?string
    {
        return \App\Services\NipService::forEmployee($this);
    }
}
