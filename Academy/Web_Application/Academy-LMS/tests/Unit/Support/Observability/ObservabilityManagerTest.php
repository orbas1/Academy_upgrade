<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Observability\Metrics\MetricTransport;
use App\Support\Observability\Metrics\StatsdClient;
use App\Support\Observability\ObservabilityManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class ObservabilityManagerTest extends TestCase
{
    public function testRecordsHttpRequestMetricsAndSlowLog(): void
    {
        $transport = new CollectingMetricTransport();
        $logger = new CollectingLogger();
        $manager = new ObservabilityManager(
            new StatsdClient($transport, 'academy', true, ['env' => 'testing']),
            $logger,
            [
                'metrics' => ['enabled' => true],
                'http' => ['slow_request_threshold_ms' => 100.0],
                'queue' => ['slow_job_threshold_ms' => 200.0],
                'logging' => [],
            ]
        );

        $manager->recordHttpRequest('GET', 'communities.show', 200, 150.0, ['user_id' => 1]);

        $this->assertCount(2, $transport->payloads);
        $this->assertStringContainsString('http.server.duration', $transport->payloads[0]);
        $this->assertStringContainsString('http.server.request', $transport->payloads[1]);
        $this->assertCount(1, $logger->records);
        $this->assertSame('warning', $logger->records[0]['level']);
    }

    public function testRecordsQueueFailuresWhenMetricsDisabled(): void
    {
        $transport = new CollectingMetricTransport();
        $logger = new CollectingLogger();
        $manager = new ObservabilityManager(
            new StatsdClient($transport, enabled: false),
            $logger,
            ['metrics' => ['enabled' => false], 'http' => [], 'queue' => []]
        );

        $manager->recordQueueFailure('TestJob', 'redis', 'default', new RuntimeException('boom'));

        $this->assertSame([], $transport->payloads);
        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
    }
}

class CollectingLogger extends NullLogger
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}

class CollectingMetricTransport implements MetricTransport
{
    /** @var list<string> */
    public array $payloads = [];

    public function send(string $payload): void
    {
        $this->payloads[] = $payload;
    }
}
