<?php

namespace Tests\Feature\Domain\Communities;

use App\Domain\Communities\Contracts\PointsService as PointsContract;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPointsRule;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PointsServiceBindingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 18, 9, 0, 0));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_awards_points_and_respects_daily_caps(): void
    {
        /** @var Community $community */
        $community = Community::factory()->create();
        /** @var User $memberUser */
        $memberUser = User::factory()->create();
        /** @var User $actor */
        $actor = User::factory()->create();

        /** @var CommunityMember $member */
        $member = CommunityMember::factory()
            ->for($community, 'community')
            ->for($memberUser, 'user')
            ->state([
                'status' => 'active',
                'points' => 0,
                'lifetime_points' => 0,
            ])
            ->create();

        CommunityPointsRule::query()->create([
            'community_id' => $community->getKey(),
            'action' => 'comment',
            'points' => 15,
            'metadata' => ['daily_cap' => 30],
            'is_active' => true,
        ]);

        /** @var PointsContract $service */
        $service = $this->app->make(PointsContract::class);

        $first = $service->award($memberUser->getKey(), 'comment', 10, [
            'community_id' => $community->getKey(),
            'actor_id' => $actor->getKey(),
            'source' => ['type' => 'comment', 'id' => 551],
        ]);

        $this->assertSame(15, $first['points']);
        $this->assertFalse($first['capped']);
        $this->assertSame(15, $member->fresh()->points);

        $second = $service->award($memberUser->getKey(), 'comment', 10, [
            'community_id' => $community->getKey(),
            'actor_id' => $actor->getKey(),
        ]);

        $this->assertSame(15, $second['points']);
        $this->assertFalse($second['capped']);
        $this->assertSame(30, $member->fresh()->points);

        $third = $service->award($memberUser->getKey(), 'comment', 10, [
            'community_id' => $community->getKey(),
            'actor_id' => $actor->getKey(),
        ]);

        $this->assertSame(0, $third['points']);
        $this->assertTrue($third['capped']);
        $this->assertSame(30, $member->fresh()->points);

        $remaining = $service->remainingForToday($memberUser->getKey(), 'comment');
        $this->assertSame('comment', $remaining['event']);
        $this->assertSame(0, $remaining['remaining']);

        $this->assertSame(2, CommunityPointsLedger::query()->count());
    }
}
