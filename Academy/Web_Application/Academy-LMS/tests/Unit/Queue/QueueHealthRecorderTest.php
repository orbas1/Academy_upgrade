<?php

namespace Tests\Unit\Queue;

use App\Models\QueueMetric;
use App\Services\Queue\QueueHealthRecorder;
use App\Services\Queue\QueueMetricsFetcher;
use App\Services\Queue\QueueMetricsSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueHealthRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_metrics_and_calculates_backlog_delta(): void
    {
        config()->set('queue.queues', ['media' => 'media']);
        config()->set('queue-monitor.connection', 'horizon');
        config()->set('queue-monitor.retention_hours', 24);

        Carbon::setTestNow('2025-01-01 00:00:00');

        $fetcher = new FakeQueueMetricsFetcher([
            [new QueueMetricsSnapshot('media', 'horizon', 10, 0, 0, 30, null, null)],
            [new QueueMetricsSnapshot('media', 'horizon', 6, 0, 0, 10, null, null)],
        ]);

        $recorder = new QueueHealthRecorder($fetcher, config());

        $firstRun = $recorder->record();
        $this->assertCount(1, $firstRun);
        $this->assertNull($firstRun->first()->backlog_delta_per_minute);

        Carbon::setTestNow('2025-01-01 00:05:00');

        $secondRun = $recorder->record();
        $this->assertCount(1, $secondRun);
        $this->assertSame(0.8, $secondRun->first()->backlog_delta_per_minute);

        $this->assertSame(2, QueueMetric::query()->count());
    }

    public function test_it_prunes_metrics_outside_retention_window(): void
    {
        config()->set('queue.queues', ['media' => 'media']);
        config()->set('queue-monitor.connection', 'horizon');
        config()->set('queue-monitor.retention_hours', 1);

        $fetcher = new FakeQueueMetricsFetcher([
            [new QueueMetricsSnapshot('media', 'horizon', 5, 0, 0, 10, null, null)],
            [new QueueMetricsSnapshot('media', 'horizon', 4, 0, 0, 8, null, null)],
        ]);

        Carbon::setTestNow('2025-01-01 00:00:00');
        $recorder = new QueueHealthRecorder($fetcher, config());
        $recorder->record();

        $this->assertSame(1, QueueMetric::query()->count());

        Carbon::setTestNow('2025-01-01 02:00:00');
        $recorder->record();

        $this->assertSame(1, QueueMetric::query()->count());
        $this->assertSame('media', QueueMetric::query()->latest('recorded_at')->first()?->queue_name);
    }
}

final class FakeQueueMetricsFetcher implements QueueMetricsFetcher
{
    /**
     * @param array<int, array<int, QueueMetricsSnapshot>> $sequence
     */
    public function __construct(private array $sequence)
    {
    }

    public function fetch(array $queueNames, string $connection): array
    {
        if (empty($this->sequence)) {
            return [];
        }

        return array_shift($this->sequence);
    }
}
