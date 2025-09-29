<?php

namespace App\Services\Queue;

final class QueueThresholdResult
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';

    /**
     * @param array<int, string> $alerts
     * @param array<string, int|float> $thresholds
     */
    public function __construct(
        public readonly string $status,
        public readonly array $alerts,
        public readonly array $thresholds,
        public readonly ?string $publicMessage
    ) {
    }

    public function isDegraded(): bool
    {
        return $this->status === self::STATUS_DEGRADED;
    }
}
