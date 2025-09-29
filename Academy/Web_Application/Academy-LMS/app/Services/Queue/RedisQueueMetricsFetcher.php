<?php

namespace App\Services\Queue;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RedisQueueMetricsFetcher implements QueueMetricsFetcher
{
    public function __construct(private readonly RedisFactory $redis)
    {
    }

    public function fetch(array $queueNames, string $connection): array
    {
        $connectionInstance = $this->redis->connection($connection);
        $prefix = config('queue-monitor.redis_prefix');

        $now = time();
        $snapshots = [];

        foreach ($queueNames as $logicalName => $queueName) {
            $resolvedQueueName = is_string($logicalName) ? $logicalName : (string) $queueName;

            $queueKey = $this->buildKey($queueName, $prefix);
            $reservedKey = $queueKey.':reserved';
            $delayedKey = $queueKey.':delayed';

            $pendingJobs = (int) $connectionInstance->llen($queueKey);
            $reservedJobs = (int) $connectionInstance->zcard($reservedKey);
            $delayedJobs = (int) $connectionInstance->zcard($delayedKey);

            $oldestPendingSeconds = $this->resolveOldestPendingAge($connectionInstance, $queueKey, $now);
            $oldestReservedSeconds = $this->resolveOldestScoreAge($connectionInstance, $reservedKey, $now);
            $oldestDelayedSeconds = $this->resolveOldestScoreAge($connectionInstance, $delayedKey, $now);

            $snapshots[] = new QueueMetricsSnapshot(
                $resolvedQueueName,
                $connection,
                $pendingJobs,
                $reservedJobs,
                $delayedJobs,
                $oldestPendingSeconds,
                $oldestReservedSeconds,
                $oldestDelayedSeconds,
            );
        }

        return $snapshots;
    }

    private function buildKey(string $queueName, ?string $prefix): string
    {
        $normalized = Str::startsWith($queueName, 'queues:') ? $queueName : 'queues:'.$queueName;

        if ($prefix !== null && $prefix !== '') {
            return $prefix.$normalized;
        }

        return $normalized;
    }

    private function resolveOldestPendingAge($connection, string $queueKey, int $now): ?int
    {
        $payload = $connection->lindex($queueKey, 0);

        if (! is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        $availableAt = (int) ($decoded['available_at'] ?? Arr::get($decoded, 'payload.available_at') ?? $now);

        return max(0, $now - $availableAt);
    }

    private function resolveOldestScoreAge($connection, string $key, int $now): ?int
    {
        $values = $connection->zrange($key, 0, 0);

        if (! is_array($values) || empty($values)) {
            return null;
        }

        $member = array_shift($values);
        $score = $connection->zscore($key, $member);

        if ($score === null || $score === false) {
            return null;
        }

        return max(0, $now - (int) $score);
    }
}
