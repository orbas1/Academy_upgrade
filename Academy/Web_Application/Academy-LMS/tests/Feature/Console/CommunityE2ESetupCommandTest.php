<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommunityE2ESetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_prepares_environment_and_persists_report(): void
    {
        Storage::fake('local');

        $this->artisan('community:e2e:setup', [
            '--skip-feature' => true,
            '--report' => 'testing/test_report.json',
        ])->assertSuccessful();

        Storage::disk('local')->assertExists('testing/test_report.json');

        $payload = json_decode(Storage::disk('local')->get('testing/test_report.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $payload['status'] ?? null);
        $this->assertSame(2, $payload['community']['member_count'] ?? null);
        $this->assertSame('active', $payload['subscription']['status'] ?? null);
        $this->assertNotEmpty($payload['leaderboard'] ?? []);
        $this->assertArrayHasKey('report_path', $payload['meta']);
    }
}
