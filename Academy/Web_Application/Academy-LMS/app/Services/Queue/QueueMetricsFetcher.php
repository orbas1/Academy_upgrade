<?php

namespace App\Services\Queue;

interface QueueMetricsFetcher
{
    /**
     * @return array<int, QueueMetricsSnapshot>
     */
    public function fetch(array $queueNames, string $connection): array;
}
