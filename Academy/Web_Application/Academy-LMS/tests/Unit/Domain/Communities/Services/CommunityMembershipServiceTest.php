<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityFollow;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Services\CommunityMembershipService;
use App\Events\Community\MemberApproved;
use App\Events\Community\MemberJoined;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommunityMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 18, 14, 15));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_join_community_restores_soft_deleted_memberships(): void
    {
        /** @var CommunityMembershipService $service */
        $service = $this->app->make(CommunityMembershipService::class);

        $community = Community::factory()->create();
        $user = User::factory()->create();

        $existing = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'left',
            'joined_at' => CarbonImmutable::now()->subMonths(2),
            'deleted_at' => CarbonImmutable::now()->subMonth(),
        ]);

        $existing->delete();

        Event::fake([MemberJoined::class, MemberApproved::class]);

        $member = $service->joinCommunity($community, $user, role: 'moderator');

        $this->assertTrue($member->exists);
        $this->assertNull($member->deleted_at);
        $this->assertSame('active', $member->status);
        $this->assertSame('moderator', $member->role);
        $this->assertTrue($member->is_online);
        $this->assertEqualsCanonicalizing([
            'community_id' => $community->getKey(),
            'follower_id' => $user->getKey(),
        ], CommunityFollow::query()->first(['community_id', 'follower_id'])->toArray());

        Event::assertDispatched(MemberJoined::class);
        Event::assertDispatched(MemberApproved::class);
    }

    public function test_update_status_emits_approval_when_transitioning_to_active(): void
    {
        /** @var CommunityMembershipService $service */
        $service = $this->app->make(CommunityMembershipService::class);

        $member = CommunityMember::factory()->create([
            'status' => 'pending',
        ]);

        Event::fake([MemberApproved::class]);

        $updated = $service->updateStatus($member, 'active');

        $this->assertSame('active', $updated->status);

        Event::assertDispatched(MemberApproved::class, function (MemberApproved $event) use ($member) {
            return $event->member->is($member);
        });
    }

    public function test_leave_community_soft_deletes_and_clears_follow(): void
    {
        /** @var CommunityMembershipService $service */
        $service = $this->app->make(CommunityMembershipService::class);

        $community = Community::factory()->create();
        $user = User::factory()->create();

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        CommunityFollow::query()->create([
            'community_id' => $community->getKey(),
            'followable_type' => Community::class,
            'followable_id' => $community->getKey(),
            'follower_id' => $user->getKey(),
            'notifications_enabled' => true,
            'followed_at' => CarbonImmutable::now()->subDay(),
        ]);

        $service->leaveCommunity($member);

        $this->assertSoftDeleted('community_members', [
            'id' => $member->getKey(),
        ]);
        $this->assertDatabaseMissing('community_follows', [
            'community_id' => $community->getKey(),
            'follower_id' => $user->getKey(),
        ]);
    }
}
