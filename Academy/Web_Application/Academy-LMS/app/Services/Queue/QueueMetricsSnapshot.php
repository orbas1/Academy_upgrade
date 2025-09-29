<?php

namespace App\Services\Queue;

final class QueueMetricsSnapshot
{
    public function __construct(
        public readonly string $queueName,
        public readonly string $connectionName,
        public readonly int $pendingJobs,
        public readonly int $reservedJobs,
        public readonly int $delayedJobs,
        public readonly ?int $oldestPendingSeconds,
        public readonly ?int $oldestReservedSeconds,
        public readonly ?int $oldestDelayedSeconds,
    ) {
    }
}
