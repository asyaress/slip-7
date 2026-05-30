<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_recovery_codes',
        'two_factor_enabled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_enabled_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function twoFactorDevices(): HasMany
    {
        return $this->hasMany(TwoFactorDevice::class);
    }

    public function confirmedTwoFactorDevices(): HasMany
    {
        return $this->twoFactorDevices()->whereNotNull('confirmed_at');
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->confirmedTwoFactorDevices()->exists();
    }
}
