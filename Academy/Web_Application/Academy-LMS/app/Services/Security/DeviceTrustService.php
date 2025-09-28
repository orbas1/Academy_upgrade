<?php

namespace App\Services\Security;

use App\Exceptions\Security\TooManyDevicesException;
use App\Models\DeviceIp;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class DeviceTrustService
{
    public function __construct(private readonly int $trustTtlDays)
    {
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

        return $record->trusted_at->greaterThan(now()->subDays(max($this->trustTtlDays, 0)));
    }

    public function recordLogin(User $user, ?string $deviceToken, string $ipAddress, ?string $sessionId, bool $markTrusted = false): void
    {
        $limitSetting = (int) (get_settings('device_limitation') ?? 1);
        $enforceLimit = $user->role !== 'admin' && $limitSetting !== 0;

        $deviceToken = $deviceToken ?: $this->fallbackToken($user, $ipAddress);

        $devices = DeviceIp::where('user_id', $user->id)->get();
        $current = $devices->firstWhere('user_agent', $deviceToken);

        if ($current) {
            $this->touchDevice($current, $ipAddress, $sessionId, $markTrusted);

            return;
        }

        if (! $enforceLimit || $devices->count() < max($limitSetting, 1)) {
            $this->createDevice($user, $deviceToken, $ipAddress, $sessionId, $markTrusted);

            return;
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

        $device->delete();
    }

    public function toggleTrust(DeviceIp $device, bool $trusted): void
    {
        $device->forceFill([
            'trusted_at' => $trusted ? now() : null,
        ])->save();
    }

    private function touchDevice(DeviceIp $device, string $ipAddress, ?string $sessionId, bool $markTrusted): void
    {
        $device->forceFill([
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
            'last_seen_at' => now(),
        ]);

        if ($markTrusted) {
            $device->trusted_at = now();
        }

        $device->save();
    }

    private function createDevice(User $user, string $deviceToken, string $ipAddress, ?string $sessionId, bool $markTrusted): void
    {
        DeviceIp::create([
            'user_id' => $user->id,
            'user_agent' => $deviceToken,
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
            'last_seen_at' => now(),
            'trusted_at' => $markTrusted ? now() : null,
        ]);
    }

    private function fallbackToken(User $user, string $ipAddress): string
    {
        return 'fallback:'.sha1($user->id.'|'.$ipAddress);
    }
}
