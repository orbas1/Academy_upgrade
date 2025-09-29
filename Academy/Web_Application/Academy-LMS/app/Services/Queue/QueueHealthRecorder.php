<?php

namespace App\Services\Queue;

use App\Models\QueueMetric;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;

class QueueHealthRecorder
{
    public function __construct(
        private readonly QueueMetricsFetcher $fetcher,
        private readonly ConfigRepository $config
    ) {
    }

    /**
     * @return Collection<int, QueueMetric>
     */
    public function record(): Collection
    {
        $queues = $this->config->get('queue.queues', []);
        $connection = (string) $this->config->get('queue-monitor.connection', 'horizon');

        if (empty($queues)) {
            return collect();
        }

        $snapshots = $this->fetcher->fetch($queues, $connection);
        $recordedAt = CarbonImmutable::now();

        $metrics = collect();

        foreach ($snapshots as $snapshot) {
            $metric = $this->storeSnapshot($snapshot, $recordedAt);
            $metrics->push($metric);
        }

        $this->pruneExpired();

        return $metrics;
    }

    private function storeSnapshot(QueueMetricsSnapshot $snapshot, CarbonImmutable $recordedAt): QueueMetric
    {
        $previous = QueueMetric::query()
            ->where('queue_name', $snapshot->queueName)
            ->orderByDesc('recorded_at')
            ->first();

        $backlogDeltaPerMinute = null;

        if ($previous !== null && $previous->recorded_at !== null) {
            $elapsedSeconds = max(1, $previous->recorded_at->diffInSeconds($recordedAt));
            $previousBacklog = $previous->pending_jobs + $previous->reserved_jobs;
            $currentBacklog = $snapshot->pendingJobs + $snapshot->reservedJobs;
            $delta = ($previousBacklog - $currentBacklog) / ($elapsedSeconds / 60);
            $backlogDeltaPerMinute = round($delta, 2);
        }

        return QueueMetric::query()->create([
            'queue_name' => $snapshot->queueName,
            'connection_name' => $snapshot->connectionName,
            'pending_jobs' => $snapshot->pendingJobs,
            'reserved_jobs' => $snapshot->reservedJobs,
            'delayed_jobs' => $snapshot->delayedJobs,
            'oldest_pending_seconds' => $snapshot->oldestPendingSeconds,
            'oldest_reserved_seconds' => $snapshot->oldestReservedSeconds,
            'oldest_delayed_seconds' => $snapshot->oldestDelayedSeconds,
            'backlog_delta_per_minute' => $backlogDeltaPerMinute,
            'recorded_at' => $recordedAt,
        ]);
    }

    private function pruneExpired(): void
    {
        $retentionHours = (int) $this->config->get('queue-monitor.retention_hours', 168);

        if ($retentionHours <= 0) {
            return;
        }

        $cutoff = CarbonImmutable::now()->subHours($retentionHours);

        QueueMetric::query()
            ->where('recorded_at', '<', $cutoff)
            ->delete();
    }
}
