<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AdminCommunityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 10, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_list_communities_with_metrics_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = $this->makeCommunity($admin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/communities')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has('data.0', fn (AssertableJson $item) => $item
                    ->where('id', $community->getKey())
                    ->where('name', 'Product Founders')
                    ->where('slug', 'product-founders')
                    ->where('visibility', 'public')
                    ->has('members_count')
                    ->has('online_count')
                    ->has('posts_per_day')
                    ->has('comments_per_day')
                    ->where('paywall_enabled', true)
                    ->has('last_activity_at')
                )
                ->where('meta.total', 1)
            );
    }

    public function test_admin_can_read_metrics_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = $this->makeCommunity($admin);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/communities/{$community->getKey()}/metrics")
            ->assertOk();

        $metrics = $response->json('data');

        $this->assertArrayHasKey('dau', $metrics);
        $this->assertArrayHasKey('wau', $metrics);
        $this->assertArrayHasKey('mrr', $metrics);
        $this->assertArrayHasKey('queue_size', $metrics);
    }

    public function test_admin_can_paginate_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = $this->makeCommunity($admin, memberCount: 5);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/communities/{$community->getKey()}/members")
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data')
                ->has('data.0.name')
                ->has('meta.pagination')
            );
    }

    public function test_admin_can_review_feed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = $this->makeCommunity($admin, postCount: 2);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/communities/{$community->getKey()}/feed")
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 2)
                ->has('data.0.body_html')
                ->has('meta.pagination')
            );
    }

    private function makeCommunity(User $owner, int $memberCount = 3, int $postCount = 1): Community
    {
        $community = Community::create([
            'slug' => 'product-founders',
            'name' => 'Product Founders',
            'visibility' => 'public',
            'join_policy' => 'open',
            'default_post_visibility' => 'community',
            'created_by' => $owner->getKey(),
            'updated_by' => $owner->getKey(),
            'launched_at' => CarbonImmutable::now()->subWeek(),
        ]);

        CommunityMember::create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subWeeks(4),
            'last_seen_at' => CarbonImmutable::now()->subDay(),
            'is_online' => true,
        ]);

        for ($i = 0; $i < $memberCount; $i++) {
            $user = User::factory()->create([
                'role' => 'student',
                'email' => "member{$i}@example.com",
            ]);

            CommunityMember::create([
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'role' => 'member',
                'status' => 'active',
                'joined_at' => CarbonImmutable::now()->subWeeks(4 - $i),
                'last_seen_at' => CarbonImmutable::now()->subDays(2),
                'is_online' => $i === 0,
            ]);
        }

        $tier = CommunitySubscriptionTier::create([
            'community_id' => $community->getKey(),
            'name' => 'Pro',
            'slug' => 'pro',
            'currency' => 'USD',
            'price_cents' => 2500,
            'billing_interval' => 'monthly',
            'trial_days' => 7,
            'is_public' => true,
        ]);

        CommunitySubscription::create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'subscription_tier_id' => $tier->getKey(),
            'status' => 'active',
            'provider' => 'stripe',
        ]);

        for ($p = 0; $p < $postCount; $p++) {
            CommunityPost::create([
                'community_id' => $community->getKey(),
                'author_id' => $owner->getKey(),
                'type' => 'text',
                'body_md' => "## Update {$p}",
                'body_html' => '<h2>Update</h2>',
                'visibility' => 'community',
                'like_count' => 2,
                'comment_count' => 1,
                'share_count' => 0,
                'view_count' => 5,
                'published_at' => CarbonImmutable::now()->subHours($p + 1),
            ]);
        }

        return $community;
    }
}
