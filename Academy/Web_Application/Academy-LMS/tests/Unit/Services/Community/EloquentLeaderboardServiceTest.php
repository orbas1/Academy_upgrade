<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Community;

use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPointsLedger;
use App\Services\Community\LeaderboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 21, 9));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_snapshot_and_leaderboard_output(): void
    {
        /** @var LeaderboardService $service */
        $service = $this->app->make(LeaderboardService::class);

        $community = Community::factory()->create();
        $memberA = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 120,
        ]);
        $memberB = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 80,
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $memberA->getKey(),
            'points_delta' => 90,
            'balance_after' => 120,
            'occurred_at' => CarbonImmutable::now()->subDay(),
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $memberB->getKey(),
            'points_delta' => 30,
            'balance_after' => 80,
            'occurred_at' => CarbonImmutable::now()->subDay(),
        ]);

        $service->snapshot($community, CommunityLeaderboardPeriod::WEEKLY);

        $leaderboard = $service->getLeaderboard($community, CommunityLeaderboardPeriod::WEEKLY);

        $this->assertCount(2, $leaderboard);
        $this->assertSame($memberA->getKey(), $leaderboard->first()['member_id']);
        $this->assertSame(1, $leaderboard->first()['rank']);
        $this->assertSame(90, $leaderboard->first()['points']);
    }

    public function test_member_standing_returns_rank_or_points(): void
    {
        /** @var LeaderboardService $service */
        $service = $this->app->make(LeaderboardService::class);

        $community = Community::factory()->create();
        $memberA = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 140,
        ]);
        $memberB = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'points' => 10,
        ]);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $memberA->getKey(),
            'points_delta' => 50,
            'balance_after' => 140,
            'occurred_at' => CarbonImmutable::now()->subHours(4),
        ]);

        $service->snapshot($community, CommunityLeaderboardPeriod::DAILY);

        $standingA = $service->getMemberStanding($memberA->fresh(), CommunityLeaderboardPeriod::DAILY);
        $standingB = $service->getMemberStanding($memberB->fresh(), CommunityLeaderboardPeriod::DAILY);

        $this->assertSame(1, $standingA['rank']);
        $this->assertSame(50, $standingA['points']);
        $this->assertNull($standingB);

        CommunityPointsLedger::factory()->create([
            'community_id' => $community->getKey(),
            'member_id' => $memberB->getKey(),
            'points_delta' => 20,
            'balance_after' => 30,
            'occurred_at' => CarbonImmutable::now()->subHour(),
        ]);

        $standingBAfter = $service->getMemberStanding($memberB->fresh(), CommunityLeaderboardPeriod::DAILY);
        $this->assertNull($standingBAfter['rank']);
        $this->assertSame(20, $standingBAfter['points']);
    }
}
