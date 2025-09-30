<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Enums\Community\CommunityMemberStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 21, 11));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_can_list_members(): void
    {
        $community = Community::factory()->create();
        $owner = User::find($community->created_by);
        Sanctum::actingAs($owner);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'status' => 'active',
            'role' => 'member',
        ]);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/members");

        $response->assertOk();
        $response->assertJsonFragment([
            'user_id' => $member->user_id,
            'role' => 'member',
        ]);
    }

    public function test_owner_can_ban_member(): void
    {
        $community = Community::factory()->create();
        $owner = User::find($community->created_by);
        Sanctum::actingAs($owner);

        $ownerMembership = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->putJson(
            "/api/v1/communities/{$community->getKey()}/members/{$member->getKey()}",
            [
                'status' => CommunityMemberStatus::BANNED->value,
                'message' => 'Code of conduct violation',
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('community_members', [
            'id' => $member->getKey(),
            'status' => CommunityMemberStatus::BANNED->value,
        ]);
    }

    public function test_setting_member_pending_is_rejected(): void
    {
        $community = Community::factory()->create();
        $owner = User::find($community->created_by);
        Sanctum::actingAs($owner);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->putJson(
            "/api/v1/communities/{$community->getKey()}/members/{$member->getKey()}",
            ['status' => CommunityMemberStatus::PENDING->value]
        );

        $response->assertStatus(422);
    }
}
