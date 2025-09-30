<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPaywallAccess;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunitySinglePurchase;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use App\Services\Community\PaywallService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentPaywallServiceTest extends TestCase
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

    public function test_paid_post_requires_entitlement(): void
    {
        /** @var PaywallService $service */
        $service = $this->app->make(PaywallService::class);

        $community = Community::factory()->create();
        $tier = CommunitySubscriptionTier::factory()->create([
            'community_id' => $community->getKey(),
        ]);

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'status' => 'active',
        ]);

        $post = CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $member->user_id,
            'visibility' => 'paid',
            'paywall_tier_id' => $tier->getKey(),
        ]);

        $this->assertFalse($service->canAccessPost($post, $member));

        $purchase = CommunitySinglePurchase::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $member->user_id,
            'purchasable_type' => CommunitySubscriptionTier::class,
            'purchasable_id' => $tier->getKey(),
            'currency' => 'USD',
            'amount_cents' => 2500,
            'provider' => 'stripe',
            'provider_reference' => 'pi_fake',
            'purchased_at' => CarbonImmutable::now(),
            'metadata' => [],
        ]);

        $service->grantSinglePurchase($member, $purchase);

        $this->assertTrue($service->canAccessPost($post->fresh(), $member->fresh()));
        $this->assertDatabaseHas('community_paywall_access', [
            'community_id' => $community->getKey(),
            'user_id' => $member->user_id,
            'subscription_tier_id' => $tier->getKey(),
        ]);
    }

    public function test_grant_subscription_access_tracks_metadata(): void
    {
        /** @var PaywallService $service */
        $service = $this->app->make(PaywallService::class);

        $community = Community::factory()->create();
        $tier = CommunitySubscriptionTier::factory()->create([
            'community_id' => $community->getKey(),
        ]);

        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'status' => 'pending',
        ]);

        $subscription = CommunitySubscription::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $member->user_id,
            'subscription_tier_id' => $tier->getKey(),
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_fake',
            'status' => 'active',
            'renews_at' => CarbonImmutable::now()->addMonth(),
            'metadata' => [
                'granted_by' => $member->user_id,
                'notes' => 'Seeded via unit test',
            ],
        ]);

        $access = $service->grantSubscriptionAccess($subscription);

        $this->assertInstanceOf(CommunityPaywallAccess::class, $access);
        $this->assertDatabaseHas('community_paywall_access', [
            'community_id' => $community->getKey(),
            'user_id' => $member->user_id,
            'subscription_tier_id' => $tier->getKey(),
            'reason' => 'tier',
        ]);
        $this->assertSame('active', $access->metadata['status']);
        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_revoke_access_marks_expiry(): void
    {
        /** @var PaywallService $service */
        $service = $this->app->make(PaywallService::class);

        $community = Community::factory()->create();
        $member = CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
        ]);

        $access = CommunityPaywallAccess::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $member->user_id,
            'subscription_tier_id' => null,
            'access_starts_at' => CarbonImmutable::now()->subWeek(),
            'access_ends_at' => null,
            'reason' => 'admin',
            'metadata' => [],
        ]);

        $service->revokeAccess($access);

        $this->assertNotNull($access->fresh()->access_ends_at);
        $this->assertSame('admin', $access->fresh()->reason);
        $this->assertArrayHasKey('revoked_at', $access->fresh()->metadata);
    }

    public function test_configure_default_tier_updates_settings(): void
    {
        /** @var PaywallService $service */
        $service = $this->app->make(PaywallService::class);

        $community = Community::factory()->create([
            'settings' => [],
        ]);
        $tier = CommunitySubscriptionTier::factory()->create([
            'community_id' => $community->getKey(),
        ]);

        $service->configureDefaultTier($community, $tier);

        $settings = $community->fresh()->settings;
        $this->assertSame($tier->getKey(), $settings['paywall']['default_tier_id']);
        $this->assertSame($tier->slug, $settings['paywall']['default_tier_slug']);
        $this->assertArrayHasKey('updated_at', $settings['paywall']);
    }
}
