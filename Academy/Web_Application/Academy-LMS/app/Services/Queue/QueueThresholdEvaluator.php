<?php

namespace App\Services\Queue;

use App\Models\QueueMetric;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class QueueThresholdEvaluator
{
    public function __construct(private readonly ConfigRepository $config)
    {
    }

    public function evaluate(QueueMetric $metric): QueueThresholdResult
    {
        $queues = $this->config->get('queue-monitor.queues', []);
        $defaultThresholds = $this->config->get('queue-monitor.default_thresholds', []);
        $queueConfig = $queues[$metric->queue_name] ?? [];
        $thresholds = array_merge($defaultThresholds, $queueConfig['thresholds'] ?? []);

        $alerts = [];

        $this->compare($alerts, $metric->pending_jobs, $thresholds, 'pending_jobs', 'Pending jobs exceed %d.');
        $this->compare($alerts, $metric->reserved_jobs, $thresholds, 'reserved_jobs', 'Reserved jobs exceed %d.');
        $this->compare($alerts, $metric->delayed_jobs, $thresholds, 'delayed_jobs', 'Delayed jobs exceed %d.');
        $this->compare($alerts, $metric->oldest_pending_seconds, $thresholds, 'oldest_pending_seconds', 'Oldest pending job has been waiting %d seconds.');
        $this->compare($alerts, $metric->oldest_reserved_seconds, $thresholds, 'oldest_reserved_seconds', 'Oldest reserved job has been running %d seconds.');
        $this->compare($alerts, $metric->oldest_delayed_seconds, $thresholds, 'oldest_delayed_seconds', 'Oldest delayed job is %d seconds from release.');

        if (array_key_exists('backlog_delta_per_minute', $thresholds) && $metric->backlog_delta_per_minute !== null) {
            $limit = (float) $thresholds['backlog_delta_per_minute'];
            if ($metric->backlog_delta_per_minute < $limit) {
                $alerts[] = sprintf(
                    'Backlog is growing at %.2f jobs/min (limit %.2f).',
                    $metric->backlog_delta_per_minute,
                    $limit
                );
            }
        }

        $status = empty($alerts) ? QueueThresholdResult::STATUS_HEALTHY : QueueThresholdResult::STATUS_DEGRADED;
        $publicMessage = $status === QueueThresholdResult::STATUS_DEGRADED
            ? ($queueConfig['public_message'] ?? null)
            : null;

        return new QueueThresholdResult($status, $alerts, $thresholds, $publicMessage);
    }

    private function compare(array &$alerts, ?int $value, array $thresholds, string $key, string $template): void
    {
        if ($value === null) {
            return;
        }

        if (! array_key_exists($key, $thresholds)) {
            return;
        }

        $threshold = (int) $thresholds[$key];

        if ($value > $threshold) {
            $alerts[] = sprintf($template, $value);
        }
    }
}
