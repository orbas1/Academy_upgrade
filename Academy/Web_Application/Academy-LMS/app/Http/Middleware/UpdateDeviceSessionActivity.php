<?php

namespace App\Http\Middleware;

use App\Services\Security\SessionTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateDeviceSessionActivity
{
    public function __construct(private readonly SessionTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $request->user()?->currentAccessToken();
        $device = $this->tokens->deviceForToken($token);

        if ($device) {
            $device->forceFill([
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
            ])->save();

            if ($token) {
                $this->tokens->markTokenUsed($token);
            }
        }

        return $next($request);
    }
}
