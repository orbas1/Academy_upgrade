<?php

namespace Tests\Feature\Queue;

use App\Models\QueueMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueHealthSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_detailed_queue_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        config()->set('queue-monitor.queues', [
            'media' => [
                'thresholds' => [
                    'pending_jobs' => 5,
                    'oldest_pending_seconds' => 30,
                ],
                'public_message' => 'Uploads running slowly.',
            ],
        ]);

        Carbon::setTestNow('2025-01-01 12:00:00');

        QueueMetric::query()->create([
            'queue_name' => 'media',
            'connection_name' => 'horizon',
            'pending_jobs' => 12,
            'reserved_jobs' => 2,
            'delayed_jobs' => 1,
            'oldest_pending_seconds' => 120,
            'oldest_reserved_seconds' => 45,
            'oldest_delayed_seconds' => 30,
            'backlog_delta_per_minute' => -3.5,
            'recorded_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ops/queue-health');

        $response->assertOk();
        $response->assertJsonPath('data.0.queue', 'media');
        $response->assertJsonPath('data.0.status', 'degraded');
        $response->assertJsonPath('data.0.public_message', 'Uploads running slowly.');
        $response->assertJsonPath('data.0.metrics.pending_jobs', 12);
        $response->assertJsonPath('data.0.metrics.reserved_jobs', 2);
        $response->assertJsonPath('data.0.thresholds.pending_jobs', 5);
        $response->assertJsonStructure(['generated_at']);
    }

    public function test_member_receives_sanitized_summary(): void
    {
        $member = User::factory()->create(['role' => 'student']);

        config()->set('queue-monitor.queues', [
            'media' => [
                'thresholds' => [
                    'pending_jobs' => 1,
                ],
                'public_message' => 'Uploads running slowly.',
            ],
        ]);

        Carbon::setTestNow('2025-01-01 15:00:00');

        QueueMetric::query()->create([
            'queue_name' => 'media',
            'connection_name' => 'horizon',
            'pending_jobs' => 3,
            'reserved_jobs' => 0,
            'delayed_jobs' => 0,
            'oldest_pending_seconds' => 60,
            'oldest_reserved_seconds' => null,
            'oldest_delayed_seconds' => null,
            'backlog_delta_per_minute' => -1.2,
            'recorded_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/ops/queue-health');

        $response->assertOk();
        $response->assertJsonPath('data.0.queue', 'media');
        $response->assertJsonPath('data.0.status', 'degraded');
        $response->assertJsonPath('data.0.public_message', 'Uploads running slowly.');
        $response->assertJsonMissingPath('data.0.metrics');
        $response->assertJsonMissingPath('data.0.thresholds');
    }
}
