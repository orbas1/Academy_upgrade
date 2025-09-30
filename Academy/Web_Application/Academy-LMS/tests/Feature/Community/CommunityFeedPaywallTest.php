<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityFeedPaywallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 22, 12));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_paid_posts_hidden_without_entitlement(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $community = Community::factory()->create([
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        $tier = CommunitySubscriptionTier::query()->create([
            'community_id' => $community->getKey(),
            'name' => 'Insider',
            'slug' => 'insider',
            'price_cents' => 3900,
            'currency' => 'USD',
            'billing_interval' => 'month',
            'metadata' => [],
        ]);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'visibility' => 'community',
        ]);

        $paid = CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'visibility' => 'paid',
            'paywall_tier_id' => $tier->getKey(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/feed?page_size=10");
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($paid->getKey(), $ids, 'Paid post should be filtered without active entitlement.');

        CommunitySubscription::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'subscription_tier_id' => $tier->getKey(),
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_200',
            'status' => 'active',
            'renews_at' => CarbonImmutable::now()->addMonth(),
            'metadata' => [],
        ]);

        $responseWithAccess = $this->getJson("/api/v1/communities/{$community->getKey()}/feed?page_size=10");
        $responseWithAccess->assertOk();
        $idsWithAccess = collect($responseWithAccess->json('data'))->pluck('id')->all();
        $this->assertContains($paid->getKey(), $idsWithAccess);
    }
}
