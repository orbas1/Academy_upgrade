<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPointsRule;
use App\Domain\Communities\Services\CommunityPointsService;
use App\Events\Community\PointsAwarded;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommunityPointsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 21, 8, 30));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_award_points_updates_member_and_creates_ledger_entry(): void
    {
        /** @var CommunityPointsService $service */
        $service = $this->app->make(CommunityPointsService::class);
        $member = CommunityMember::factory()->create([
            'points' => 10,
            'lifetime_points' => 20,
        ]);
        $actor = User::factory()->create();

        Event::fake([PointsAwarded::class]);

        $ledger = $service->awardPoints($member, 'post.create', 35, $actor, [
            'source_type' => 'post',
            'source_id' => 551,
        ]);

        $member->refresh();

        $this->assertSame(45, $member->points);
        $this->assertSame(55, $member->lifetime_points);
        $this->assertInstanceOf(CommunityPointsLedger::class, $ledger);
        $this->assertDatabaseHas('community_points_ledger', [
            'id' => $ledger->getKey(),
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'action' => 'post.create',
            'points_delta' => 35,
            'balance_after' => 45,
            'source_type' => 'post',
            'source_id' => 551,
            'acted_by' => $actor->getKey(),
        ]);

        Event::assertDispatched(PointsAwarded::class, function (PointsAwarded $event) use ($member, $ledger) {
            return $event->entry->is($ledger)
                && $event->member->is($member)
                && $event->delta === 35
                && $event->action === 'post.create';
        });
    }

    public function test_apply_rule_enforces_daily_cap(): void
    {
        /** @var CommunityPointsService $service */
        $service = $this->app->make(CommunityPointsService::class);

        $member = CommunityMember::factory()->create([
            'points' => 50,
            'lifetime_points' => 150,
        ]);

        $rule = CommunityPointsRule::query()->create([
            'community_id' => $member->community_id,
            'action' => 'comment.create',
            'points' => 15,
            'metadata' => ['daily_cap' => 30],
            'is_active' => true,
        ]);

        CommunityPointsLedger::query()->create([
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'action' => 'comment.create',
            'points_delta' => 20,
            'balance_after' => 70,
            'occurred_at' => CarbonImmutable::now()->subHours(1),
        ]);

        CommunityPointsLedger::query()->create([
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'action' => 'comment.create',
            'points_delta' => 10,
            'balance_after' => 80,
            'occurred_at' => CarbonImmutable::now()->subMinutes(10),
        ]);

        $result = $service->applyRule($rule, $member, ['actor' => User::factory()->create()]);

        $this->assertNull($result, 'Rule should not award points beyond configured daily cap.');
        $this->assertSame(50, $member->fresh()->points);
    }

    public function test_recalculate_member_rebuilds_balances(): void
    {
        /** @var CommunityPointsService $service */
        $service = $this->app->make(CommunityPointsService::class);

        $member = CommunityMember::factory()->create([
            'points' => 0,
            'lifetime_points' => 0,
        ]);

        CommunityPointsLedger::factory()->create([
            'member_id' => $member->getKey(),
            'community_id' => $member->community_id,
            'points_delta' => 25,
            'balance_after' => 25,
            'occurred_at' => CarbonImmutable::now()->subDay(),
        ]);

        CommunityPointsLedger::factory()->create([
            'member_id' => $member->getKey(),
            'community_id' => $member->community_id,
            'points_delta' => -5,
            'balance_after' => 20,
            'occurred_at' => CarbonImmutable::now()->subHours(2),
        ]);

        CommunityPointsLedger::factory()->create([
            'member_id' => $member->getKey(),
            'community_id' => $member->community_id,
            'points_delta' => 40,
            'balance_after' => 60,
            'occurred_at' => CarbonImmutable::now()->subMinutes(5),
        ]);

        $service->recalculateMember($member);

        $member->refresh();

        $this->assertSame(60, $member->points);
        $this->assertSame(65, $member->lifetime_points, 'Lifetime points should only include positive deltas.');
    }
}
