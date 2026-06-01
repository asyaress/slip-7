<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyTunjanganRate extends Model
{
    protected $fillable = ['bulan', 'tahun', 'rates'];

    protected function casts(): array
    {
        return [
            'rates' => 'array',
        ];
    }
}
