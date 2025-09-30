<?php

namespace App\Services\Security;

use App\Exceptions\Security\TooManyDevicesException;
use App\Models\DeviceIp;
use App\Models\User;
use App\Support\Security\DeviceMetadata;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class DeviceTrustService
{
    public function __construct(
        private readonly Repository $config,
        private readonly SessionTokenService $tokens
    ) {
    }

    public function deviceIsTrusted(User $user, ?string $deviceToken): bool
    {
        if (! $user->hasTwoFactorEnabled() || empty($deviceToken)) {
            return false;
        }

        $record = DeviceIp::where('user_id', $user->id)
            ->where('user_agent', $deviceToken)
            ->first();

        if (! $record || ! $record->trusted_at) {
            return false;
        }

        return $record->trusted_at->greaterThan(now()->subDays(max($this->trustTtlDays(), 0)));
    }

    public function recordLogin(User $user, DeviceMetadata $metadata): DeviceIp
    {
        $deviceToken = $metadata->deviceToken ?: $this->fallbackToken($user, $metadata->ipAddress);
        /** @var Collection<int, DeviceIp> $devices */
        $devices = DeviceIp::where('user_id', $user->id)->get();
        $current = $devices->firstWhere('user_agent', $deviceToken);

        if ($current) {
            return $this->touchDevice($current, $metadata);
        }

        if ($this->canRegisterNewDevice($user, $devices)) {
            return $this->createDevice($user, $deviceToken, $metadata);
        }

        $replacement = DeviceIp::where('user_id', $user->id)->orderBy('updated_at')->first();

        if ($replacement) {
            $verificationLink = route('login', ['user_agent' => $replacement->user_agent]);
            $data = ['verification_link' => $verificationLink];

            Mail::send('email.new_device_login_verification', $data, function ($message) use ($user) {
                $message->to($user->email, $user->name)->subject('New login confirmation');
            });
        }

        throw new TooManyDevicesException(get_phrase('A confirmation email has been sent. Please check your inbox to confirm access to this account from this device.'));
    }

    public function removeDevice(DeviceIp $device): void
    {
        if ($device->session_id) {
            $path = storage_path('framework/sessions/'.$device->session_id);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $this->tokens->revokeDevice($device);

        $device->forceFill([
            'session_id' => null,
            'revoked_at' => now(),
        ])->save();
    }

    public function toggleTrust(DeviceIp $device, bool $trusted): void
    {
        $device->forceFill([
            'trusted_at' => $trusted ? now() : null,
        ])->save();
    }

    private function touchDevice(DeviceIp $device, DeviceMetadata $metadata): DeviceIp
    {
        $device->forceFill([
            'ip_address' => $metadata->ipAddress,
            'session_id' => $metadata->sessionId,
            'last_seen_at' => now(),
            'device_name' => $metadata->deviceName ?: $device->device_name,
            'platform' => $metadata->platform ?: $device->platform,
            'app_version' => $metadata->appVersion ?: $device->app_version,
            'last_headers' => array_filter($metadata->headers),
            'revoked_at' => null,
        ]);

        if ($metadata->rememberDevice) {
            $device->trusted_at = now();
        }

        $device->save();

        return $device;
    }

    private function createDevice(User $user, string $deviceToken, DeviceMetadata $metadata): DeviceIp
    {
        return DeviceIp::create([
            'user_id' => $user->id,
            'user_agent' => $deviceToken,
            'ip_address' => $metadata->ipAddress,
            'session_id' => $metadata->sessionId,
            'last_seen_at' => now(),
            'trusted_at' => $metadata->rememberDevice ? now() : null,
            'device_name' => $metadata->deviceName,
            'platform' => $metadata->platform,
            'app_version' => $metadata->appVersion,
            'last_headers' => array_filter($metadata->headers),
        ]);
    }

    private function fallbackToken(User $user, string $ipAddress): string
    {
        return 'fallback:'.sha1($user->id.'|'.$ipAddress);
    }

    private function canRegisterNewDevice(User $user, Collection $devices): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $limitSetting = (int) (get_settings('device_limitation') ?? 1);
        $configuredLimit = max((int) $this->config->get('security.device_trust.max_devices', 5), 1);

        if ($limitSetting === 0) {
            return true;
        }

        $maxDevices = max($configuredLimit, $limitSetting);

        return $devices->count() < $maxDevices;
    }

    private function trustTtlDays(): int
    {
        return (int) $this->config->get('security.device_trust.ttl_days', 60);
    }
}
