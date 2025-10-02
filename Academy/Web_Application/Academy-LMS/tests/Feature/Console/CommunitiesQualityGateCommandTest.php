<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Database\Seeders\Communities\CommunityFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitiesQualityGateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_gate_reports_failures_when_requirements_missing(): void
    {
        config([
            'stripe.secret_key' => null,
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'search.driver' => 'database',
            'search.meilisearch.host' => null,
        ]);

        $this->artisan('communities:quality-gate --json')
            ->expectsOutputToContain('"passed": false')
            ->expectsOutputToContain('schema')
            ->assertExitCode(1);
    }

    public function test_quality_gate_reports_success_once_prerequisites_met(): void
    {
        config([
            'stripe.secret_key' => 'sk_test_orbas',
            'queue.default' => 'redis',
            'cache.default' => 'redis',
            'search.driver' => 'meilisearch',
            'search.meilisearch.host' => 'http://meilisearch:7700',
        ]);

        $this->seed(CommunityFoundationSeeder::class);

        $this->artisan('communities:quality-gate --json')
            ->expectsOutputToContain('"passed": true')
            ->assertExitCode(0);
    }
}
