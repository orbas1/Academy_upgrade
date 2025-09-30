<?php

namespace App\Services\Security;

use App\Models\DeviceAccessToken;
use App\Models\DeviceIp;
use App\Models\User;
use Illuminate\Contracts\Config\Repository;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class SessionTokenService
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function createTokenForDevice(User $user, DeviceIp $device, array $abilities = ['*']): NewAccessToken
    {
        $tokenName = sprintf('device:%s', $device->user_agent);

        $existing = $user->tokens()->where('name', $tokenName)->get();
        foreach ($existing as $token) {
            $this->revokeToken($token);
        }

        $expiresAt = $this->determineExpiry();
        $token = $user->createToken($tokenName, $abilities, $expiresAt);

        DeviceAccessToken::updateOrCreate(
            [
                'device_ip_id' => $device->id,
                'token_id' => $token->accessToken->id,
            ],
            [
                'last_used_at' => now(),
            ]
        );

        $this->enforceTokenBudget($user);

        return $token;
    }

    public function markTokenUsed(PersonalAccessToken $token): void
    {
        DeviceAccessToken::where('token_id', $token->id)->update(['last_used_at' => now()]);
    }

    public function deviceForToken(?PersonalAccessToken $token): ?DeviceIp
    {
        if (! $token) {
            return null;
        }

        return DeviceAccessToken::with('device')
            ->where('token_id', $token->id)
            ->first()?->device;
    }

    public function revokeToken(PersonalAccessToken $token): void
    {
        DeviceAccessToken::where('token_id', $token->id)->delete();
        $token->delete();
    }

    public function revokeDevice(DeviceIp $device): void
    {
        $device->tokens()->each(function (DeviceAccessToken $link) {
            if ($link->token) {
                $link->token->delete();
            }

            $link->delete();
        });

        $device->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    private function enforceTokenBudget(User $user): void
    {
        $budget = (int) $this->config->get('security.sessions.max_parallel_tokens', 10);

        if ($budget <= 0) {
            return;
        }

        $tokens = DeviceAccessToken::query()
            ->whereHas('device', fn ($query) => $query->where('user_id', $user->id))
            ->with(['token', 'device'])
            ->orderByDesc('last_used_at')
            ->get();

        if ($tokens->count() <= $budget) {
            return;
        }

        $tokens->slice($budget)->each(function (DeviceAccessToken $link) {
            if ($link->token) {
                $link->token->delete();
            }

            $link->delete();
        });
    }

    private function determineExpiry(): ?\DateTimeInterface
    {
        $minutes = config('sanctum.expiration');
        if (! $minutes) {
            return null;
        }

        return now()->addMinutes((int) $minutes);
    }
}
