<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityLeaderboard;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Services\CommunityLeaderboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 19, 12));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_generate_weekly_leaderboard_ranks_members_by_points(): void
    {
        /** @var CommunityLeaderboardService $service */
        $service = $this->app->make(CommunityLeaderboardService::class);

        $community = Community::factory()->create();
        $alice = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 0,
        ]);
        $bob = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 0,
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $alice->getKey(),
            'points_delta' => 120,
            'balance_after' => 120,
            'occurred_at' => CarbonImmutable::now()->subDays(1),
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $bob->getKey(),
            'points_delta' => 80,
            'balance_after' => 80,
            'occurred_at' => CarbonImmutable::now()->subDays(2),
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $bob->getKey(),
            'points_delta' => 40,
            'balance_after' => 120,
            'occurred_at' => CarbonImmutable::now()->subHours(6),
        ]);

        $leaderboard = $service->generate($community, 'weekly', limit: 5);

        $this->assertInstanceOf(CommunityLeaderboard::class, $leaderboard);
        $this->assertSame($community->getKey(), $leaderboard->community_id);
        $this->assertSame('weekly', $leaderboard->period);
        $this->assertCount(2, $leaderboard->entries);
        $this->assertSame($bob->getKey(), $leaderboard->entries[0]['member_id']);
        $this->assertSame(120, $leaderboard->entries[0]['points']);
        $this->assertSame($alice->getKey(), $leaderboard->entries[1]['member_id']);
        $this->assertSame(120, $leaderboard->entries[1]['points']);
        $this->assertArrayHasKey('generated_at', $leaderboard->metadata);
    }

    public function test_generate_all_time_leaderboard_persists_entries(): void
    {
        /** @var CommunityLeaderboardService $service */
        $service = $this->app->make(CommunityLeaderboardService::class);

        $community = Community::factory()->create();
        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $member->getKey(),
            'points_delta' => 55,
            'balance_after' => 55,
            'occurred_at' => CarbonImmutable::now()->subMonths(2),
        ]);

        $service->generate($community, 'all_time', limit: 10);

        $stored = CommunityLeaderboard::query()
            ->where('community_id', $community->getKey())
            ->where('period', 'all_time')
            ->first();

        $this->assertNotNull($stored);
        $this->assertSame(55, $stored->entries[0]['points']);
        $this->assertNull($stored->starts_on);
        $this->assertNull($stored->ends_on);
    }
}
