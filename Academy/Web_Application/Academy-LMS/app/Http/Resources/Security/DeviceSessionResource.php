<?php

namespace App\Http\Resources\Security;

use App\Models\DeviceIp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeviceIp */
class DeviceSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentToken = $request->user()?->currentAccessToken();
        $sessionTokens = app(\App\Services\Security\SessionTokenService::class);
        $currentDevice = $sessionTokens->deviceForToken($currentToken);

        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'ip_address' => $this->ip_address,
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
            'trusted_at' => optional($this->trusted_at)->toIso8601String(),
            'revoked_at' => optional($this->revoked_at)->toIso8601String(),
            'is_trusted' => ! is_null($this->trusted_at) && ($this->revoked_at === null),
            'is_revoked' => ! is_null($this->revoked_at),
            'is_current_device' => $currentDevice?->id === $this->id,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'label' => $this->label,
        ];
    }
}
