<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Search;

use App\Domain\Search\Services\SearchVisibilityService;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPaywallAccess;
use App\Models\Community\CommunitySinglePurchase;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'search.visibility.token_secret' => 'test-secret',
            'search.visibility.ttl' => 600,
        ]);

        app()->forgetInstance(SearchVisibilityService::class);
        app()->forgetInstance(SearchVisibilityTokenService::class);
    }

    public function test_guest_context_allows_public_only(): void
    {
        $service = app(SearchVisibilityService::class);
        $context = $service->forUser(null);

        $this->assertTrue($context->includePublic);
        $this->assertFalse($context->includeCommunity);
        $this->assertFalse($context->includePaid);
        $this->assertSame([], $context->communityIds);
    }

    public function test_authenticated_context_includes_membership_and_entitlements(): void
    {
        $user = User::factory()->create();

        $community = Community::query()->create([
            'slug' => Str::slug('Alpha ' . Str::random(6)),
            'name' => 'Alpha',
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        $tier = CommunitySubscriptionTier::query()->create([
            'community_id' => $community->getKey(),
            'name' => 'Pro',
            'slug' => 'pro',
            'currency' => 'USD',
            'price_cents' => 1200,
            'billing_interval' => 'monthly',
        ]);

        CommunityMember::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        CommunitySubscription::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'subscription_tier_id' => $tier->getKey(),
            'status' => 'active',
            'renews_at' => now()->addMonth(),
        ]);

        $secondCommunity = Community::query()->create([
            'slug' => Str::slug('Beta ' . Str::random(6)),
            'name' => 'Beta',
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        CommunityMember::query()->create([
            'community_id' => $secondCommunity->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        CommunityPaywallAccess::query()->create([
            'community_id' => $secondCommunity->getKey(),
            'user_id' => $user->getKey(),
            'subscription_tier_id' => null,
            'access_starts_at' => now()->subDay(),
            'access_ends_at' => now()->addDay(),
        ]);

        CommunitySinglePurchase::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'purchasable_type' => CommunitySubscriptionTier::class,
            'purchasable_id' => $tier->getKey(),
            'currency' => 'USD',
            'amount_cents' => 1200,
            'provider' => 'stripe',
            'purchased_at' => now()->subHour(),
        ]);

        $service = app(SearchVisibilityService::class);
        $context = $service->forUser($user);

        $this->assertTrue($context->includePublic);
        $this->assertTrue($context->includeCommunity);
        $this->assertTrue($context->includePaid);
        $this->assertContains($community->getKey(), $context->communityIds);
        $this->assertContains($secondCommunity->getKey(), $context->unrestrictedPaidCommunityIds);
        $this->assertContains($tier->getKey(), $context->subscriptionTierIds);

        $tokenService = app(SearchVisibilityTokenService::class);
        $issued = $tokenService->issue($context);

        $this->assertArrayHasKey('token', $issued);
        $this->assertNotEmpty($issued['filters']);

        $validated = $tokenService->validate($issued['token']);
        $this->assertEqualsCanonicalizing($context->communityIds, $validated->communityIds);
        $this->assertEqualsCanonicalizing($context->subscriptionTierIds, $validated->subscriptionTierIds);
    }
}

