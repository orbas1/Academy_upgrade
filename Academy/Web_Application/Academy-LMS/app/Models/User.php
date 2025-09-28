<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomEmailVerificationNotification;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Support\Facades\Crypt;
use App\Casts\EncryptedAttribute;

class User extends Authenticatable implements MustVerifyEmail

{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'array',
        'skills' => EncryptedAttribute::class,
        'social_links' => EncryptedAttribute::class,
        'about' => EncryptedAttribute::class,
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function getTwoFactorSecret(): ?string
    {
        if (! $this->two_factor_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->two_factor_secret);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setTwoFactorSecret(?string $secret): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret ? Crypt::encryptString($secret) : null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function clearTwoFactorCredentials(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomEmailVerificationNotification());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
