<?php

namespace App\Support\Security;

use Illuminate\Http\Request;

class DeviceMetadata
{
    public function __construct(
        public readonly string $ipAddress,
        public readonly ?string $deviceToken,
        public readonly ?string $sessionId = null,
        public readonly ?string $deviceName = null,
        public readonly ?string $platform = null,
        public readonly ?string $appVersion = null,
        public readonly bool $rememberDevice = false,
        public readonly array $headers = []
    ) {
    }

    public static function fromRequest(Request $request, array $overrides = []): self
    {
        $headers = [
            'X-Device-Id' => (string) $request->headers->get('X-Device-Id'),
            'X-Device-Name' => (string) $request->headers->get('X-Device-Name'),
            'X-Device-Platform' => (string) $request->headers->get('X-Device-Platform'),
            'X-App-Version' => (string) $request->headers->get('X-App-Version'),
        ];

        $headers = array_filter($headers, fn ($value) => $value !== '');

        $payload = array_merge([
            'ipAddress' => $request->ip(),
            'deviceToken' => $request->input('device_token') ?: ($headers['X-Device-Id'] ?? null) ?: $request->header('X-Device-Id'),
            'sessionId' => $request->session()?->getId(),
            'deviceName' => $headers['X-Device-Name'] ?? null,
            'platform' => $headers['X-Device-Platform'] ?? $request->header('User-Agent'),
            'appVersion' => $headers['X-App-Version'] ?? null,
            'rememberDevice' => (bool) $request->boolean('remember_device'),
            'headers' => $headers,
        ], $overrides);

        return new self(
            ipAddress: (string) ($payload['ipAddress'] ?? $request->ip()),
            deviceToken: self::truncate(($payload['deviceToken'] ?? null)),
            sessionId: self::truncate($payload['sessionId'] ?? null, 120),
            deviceName: self::truncate($payload['deviceName'] ?? null, 255),
            platform: self::truncate($payload['platform'] ?? null, 120),
            appVersion: self::truncate($payload['appVersion'] ?? null, 64),
            rememberDevice: (bool) ($payload['rememberDevice'] ?? false),
            headers: $payload['headers'] ?? []
        );
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            ipAddress: (string) ($payload['ip_address'] ?? '0.0.0.0'),
            deviceToken: isset($payload['device_token']) ? self::truncate($payload['device_token']) : null,
            sessionId: isset($payload['session_id']) ? self::truncate($payload['session_id'], 120) : null,
            deviceName: isset($payload['device_name']) ? self::truncate($payload['device_name'], 255) : null,
            platform: isset($payload['platform']) ? self::truncate($payload['platform'], 120) : null,
            appVersion: isset($payload['app_version']) ? self::truncate($payload['app_version'], 64) : null,
            rememberDevice: (bool) ($payload['remember_device'] ?? false),
            headers: is_array($payload['headers'] ?? null) ? $payload['headers'] : []
        );
    }

    public function toArray(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'device_token' => $this->deviceToken,
            'session_id' => $this->sessionId,
            'device_name' => $this->deviceName,
            'platform' => $this->platform,
            'app_version' => $this->appVersion,
            'remember_device' => $this->rememberDevice,
            'headers' => $this->headers,
        ];
    }

    private static function truncate(?string $value, int $length = 191): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_strimwidth($value, 0, $length, '');
    }
}
