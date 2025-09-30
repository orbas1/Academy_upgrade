<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityControllerTest extends TestCase
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

    public function test_index_returns_paginated_communities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $communities = Community::factory()->count(2)->create([
            'visibility' => 'public',
            'join_policy' => 'open',
            'default_post_visibility' => 'community',
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
            'launched_at' => CarbonImmutable::now()->subWeek(),
        ]);

        $communities->each(function (Community $community) use ($user): void {
            CommunityMember::factory()->create([
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => CarbonImmutable::now()->subWeek(),
                'last_seen_at' => CarbonImmutable::now()->subDay(),
            ]);
        });

        $response = $this->getJson('/api/v1/communities');

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame(2, $payload['meta']['total']);
        $this->assertCount(2, $payload['data']);
        $this->assertArrayHasKey('metrics', $payload['data'][0]);
    }

    public function test_authenticated_user_can_create_community(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/communities', [
            'name' => 'Product Guild',
            'slug' => 'product-guild',
            'visibility' => 'public',
            'tagline' => 'Build better products together',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('communities', [
            'slug' => 'product-guild',
            'name' => 'Product Guild',
            'created_by' => $user->getKey(),
        ]);

        $this->assertDatabaseHas('community_members', [
            'community_id' => Community::where('slug', 'product-guild')->first()->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);
    }
}
