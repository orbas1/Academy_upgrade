<?php

namespace Tests\Feature\Domain\Communities;

use App\Domain\Communities\Contracts\MembershipService as MembershipContract;
use App\Enums\Community\CommunityJoinPolicy;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class MembershipServiceBindingTest extends TestCase
{
    use RefreshDatabase;

    public function testItBindsDomainContractToLegacyMembershipService(): void
    {
        /** @var Community $community */
        $community = Community::factory()->create([
            'join_policy' => CommunityJoinPolicy::REQUEST->value,
        ]);

        /** @var User $actor */
        $actor = User::factory()->create();

        CommunityMember::factory()
            ->for($community, 'community')
            ->for($actor, 'user')
            ->state([
                'role' => 'owner',
                'status' => 'active',
            ])
            ->create();

        /** @var User $memberUser */
        $memberUser = User::factory()->create();

        $service = $this->app->make(MembershipContract::class);

        $snapshot = $service->join($community->getKey(), $memberUser->getKey(), [
            'message' => 'Excited to collaborate!',
        ]);

        $this->assertSame($community->getKey(), $snapshot['community_id']);
        $this->assertSame($memberUser->getKey(), $snapshot['user_id']);
        $this->assertSame('pending', $snapshot['status']);
        $this->assertSame('member', $snapshot['role']);
        $this->assertInstanceOf(Carbon::class, $snapshot['requested_at']);

        $approval = $service->approve($community->getKey(), $memberUser->getKey(), $actor->getKey());

        $this->assertSame('active', $approval['status']);
        $this->assertSame('member', $approval['role']);
        $this->assertSame($actor->getKey(), $approval['approved_by']);
        $this->assertInstanceOf(Carbon::class, $approval['approved_at']);

        $this->assertDatabaseHas('community_members', [
            'community_id' => $community->getKey(),
            'user_id' => $memberUser->getKey(),
            'status' => 'active',
        ]);
    }
}

