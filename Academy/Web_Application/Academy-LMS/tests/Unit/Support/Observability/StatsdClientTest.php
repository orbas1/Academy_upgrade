<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Observability\Metrics\MetricTransport;
use App\Support\Observability\Metrics\StatsdClient;
use PHPUnit\Framework\TestCase;

class StatsdClientTest extends TestCase
{
    public function testTimingFormatsMetricAndTags(): void
    {
        $transport = new InMemoryMetricTransport();
        $client = new StatsdClient($transport, 'academy', true, ['env' => 'testing']);

        $client->timing('http.server.duration', 123.4567, ['route' => 'communities.show', 'status_code' => '200']);

        $this->assertCount(1, $transport->payloads);
        $payload = $transport->payloads[0];
        $this->assertStringContainsString('academy.http.server.duration:123.4567|ms', $payload);
        $this->assertStringContainsString('|#env:testing,route:communities.show,status_code:200', $payload);
    }

    public function testDisabledClientSkipsTransmission(): void
    {
        $transport = new InMemoryMetricTransport();
        $client = new StatsdClient($transport, enabled: false);

        $client->increment('queue.job.processed', ['queue' => 'notifications']);

        $this->assertSame([], $transport->payloads);
    }
}

class InMemoryMetricTransport implements MetricTransport
{
    /** @var list<string> */
    public array $payloads = [];

    public function send(string $payload): void
    {
        $this->payloads[] = $payload;
    }
}
