<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\NotificationProviderStatus;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NotificationProviderHealthService
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function isHealthy(string $channel, string $provider): bool
    {
        $status = $this->getStatus($channel, $provider);

        if ($status === null) {
            return true;
        }

        if ($status->healthy) {
            return true;
        }

        $cooldown = $this->cooldownSeconds($channel, $provider);
        if ($cooldown === null || $status->last_failure_at === null) {
            return $status->healthy;
        }

        return $status->last_failure_at->diffInSeconds(now()) >= $cooldown;
    }

    public function markFailure(string $channel, string $provider, string $reason, array $context = []): void
    {
        $status = $this->getStatus($channel, $provider, createIfMissing: true);
        $threshold = $this->failureThreshold($channel, $provider);
        $status->markFailure($reason, $context, $threshold);
        $this->remember($status);
    }

    public function markSuccess(string $channel, string $provider): void
    {
        $status = $this->getStatus($channel, $provider, createIfMissing: true);
        $status->markSuccess();
        $this->remember($status);
    }

    protected function getStatus(string $channel, string $provider, bool $createIfMissing = false): ?NotificationProviderStatus
    {
        $cacheKey = $this->cacheKey($channel, $provider);

        return $this->cache->remember($cacheKey, now()->addMinutes(5), function () use ($channel, $provider, $createIfMissing) {
            $record = NotificationProviderStatus::query()
                ->where('channel', $channel)
                ->where('provider', $provider)
                ->first();

            if ($record === null && $createIfMissing) {
                $record = NotificationProviderStatus::query()->create([
                    'channel' => $channel,
                    'provider' => $provider,
                ]);
            }

            return $record;
        });
    }

    protected function remember(NotificationProviderStatus $status): void
    {
        $this->cache->put($this->cacheKey($status->channel, $status->provider), $status, now()->addMinutes(5));
    }

    protected function cacheKey(string $channel, string $provider): string
    {
        return 'messaging:provider-status:'.Str::lower($channel).':'.Str::lower($provider);
    }

    protected function failureThreshold(string $channel, string $provider): int
    {
        $config = config('messaging.'.Str::lower($channel).'.providers.'.$provider, []);

        return (int) Arr::get($config, 'failures_to_trip', 3);
    }

    protected function cooldownSeconds(string $channel, string $provider): ?int
    {
        $config = config('messaging.'.Str::lower($channel).'.providers.'.$provider, []);

        $cooldown = Arr::get($config, 'cooldown_seconds');

        return $cooldown === null ? null : (int) $cooldown;
    }
}
