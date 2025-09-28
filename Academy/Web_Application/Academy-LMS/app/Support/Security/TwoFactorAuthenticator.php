<?php

namespace App\Support\Security;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;

class TwoFactorAuthenticator
{
    public function __construct(private readonly string $issuer, private readonly int $window)
    {
    }

    public function generateSecretFor(User $user): string
    {
        $secret = TOTP::create()->getSecret();
        $user->setTwoFactorSecret($secret);
        $user->forceFill(['two_factor_recovery_codes' => null])->save();

        return $secret;
    }

    public function getSecret(User $user): ?string
    {
        return $user->getTwoFactorSecret();
    }

    public function provisioningUri(User $user): ?string
    {
        $secret = $this->getSecret($user);

        if (! $secret) {
            return null;
        }

        $totp = $this->makeTotp($secret, $user->email ?? $user->name ?? 'user');

        return $totp->getProvisioningUri();
    }

    public function verify(User $user, string $code): bool
    {
        $secret = $this->getSecret($user);

        if (! $secret) {
            return false;
        }

        $totp = $this->makeTotp($secret, $user->email ?? $user->name ?? 'user');

        return $totp->verify($code, null, $this->window);
    }

    public function generateRecoveryCodes(User $user, int $total = 10): array
    {
        $codes = collect(range(1, $total))
            ->map(fn () => strtoupper(Str::random(10)))
            ->values()
            ->all();

        $user->forceFill([
            'two_factor_recovery_codes' => collect($codes)
                ->map(fn (string $code) => Hash::make($code))
                ->values()
                ->all(),
        ])->save();

        return $codes;
    }

    public function useRecoveryCode(User $user, string $input): bool
    {
        $codes = Arr::wrap($user->two_factor_recovery_codes);

        if (empty($codes)) {
            return false;
        }

        foreach ($codes as $index => $code) {
            if (Hash::check($input, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private function makeTotp(string $secret, string $label): TOTP
    {
        $totp = TOTP::create($secret, 30, 'sha1', 6);
        $totp->setLabel($label);
        $totp->setIssuer($this->issuer);

        return $totp;
    }
}
