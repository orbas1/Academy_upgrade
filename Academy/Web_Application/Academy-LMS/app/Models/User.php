<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Domain\Search\Concerns\Searchable;
use App\Domain\Search\Transformers\MemberSearchTransformer;
use App\Notifications\CustomEmailVerificationNotification;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use Searchable;

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
        'email_verified_at',
        'analytics_consent_at',
        'analytics_consent_version',
        'analytics_consent_revoked_at'

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
        'analytics_consent_at' => 'datetime',
        'analytics_consent_revoked_at' => 'datetime',
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

    public function toSearchRecord(): array
    {
        return app(MemberSearchTransformer::class)->fromModel($this);
    }

    public function grantAnalyticsConsent(?string $version = null): void
    {
        $this->forceFill([
            'analytics_consent_at' => now(),
            'analytics_consent_version' => $version ?: config('analytics.consent.version'),
            'analytics_consent_revoked_at' => null,
        ])->save();
    }

    public function revokeAnalyticsConsent(): void
    {
        $this->forceFill([
            'analytics_consent_revoked_at' => now(),
        ])->save();
    }

    public function hasAnalyticsConsent(?string $version = null): bool
    {
        if (! $this->analytics_consent_at || $this->analytics_consent_revoked_at) {
            return false;
        }

        $expected = $version ?: config('analytics.consent.version');
        if (! $expected) {
            return true;
        }

        return $this->analytics_consent_version === $expected;
    }
}
