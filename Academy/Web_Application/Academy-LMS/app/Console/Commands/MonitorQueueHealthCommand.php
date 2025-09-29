<?php

namespace App\Console\Commands;

use App\Events\Queue\QueueBacklogDetected;
use App\Models\QueueMetric;
use App\Services\Queue\QueueHealthRecorder;
use App\Services\Queue\QueueThresholdEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class MonitorQueueHealthCommand extends Command
{
    protected $signature = 'queues:monitor';

    protected $description = 'Capture queue metrics and emit alerts when thresholds are exceeded.';

    public function __construct(
        private readonly QueueHealthRecorder $recorder,
        private readonly QueueThresholdEvaluator $evaluator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $metrics = $this->recorder->record();

        if ($metrics->isEmpty()) {
            $this->info('No queues configured for monitoring.');

            return self::SUCCESS;
        }

        $metrics->each(function (QueueMetric $metric): void {
            $result = $this->evaluator->evaluate($metric);

            $context = [
                'queue' => $metric->queue_name,
                'pending' => $metric->pending_jobs,
                'reserved' => $metric->reserved_jobs,
                'delayed' => $metric->delayed_jobs,
                'oldest_pending_seconds' => $metric->oldest_pending_seconds,
                'oldest_reserved_seconds' => $metric->oldest_reserved_seconds,
                'oldest_delayed_seconds' => $metric->oldest_delayed_seconds,
                'backlog_delta_per_minute' => $metric->backlog_delta_per_minute,
                'status' => $result->status,
            ];

            if ($result->isDegraded()) {
                $this->warn(sprintf('[%s] queue degraded: %s', $metric->queue_name, implode(' | ', $result->alerts)));
                Log::channel('stack')->warning('Queue backlog detected', array_merge($context, ['alerts' => $result->alerts]));
                Event::dispatch(new QueueBacklogDetected($metric, $result->alerts));
            } else {
                $this->info(sprintf('[%s] queue healthy', $metric->queue_name));
                Log::channel('stack')->info('Queue healthy', $context);
            }
        });

        return self::SUCCESS;
    }
}
