<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPaywallAccess;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Domain\Communities\Services\CommunityPaywallService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityPaywallServiceTest extends TestCase
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

    public function test_check_access_respects_paid_post_requirements(): void
    {
        /** @var CommunityPaywallService $service */
        $service = $this->app->make(CommunityPaywallService::class);

        $community = Community::factory()->create();
        $user = User::factory()->create();
        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        $tier = CommunitySubscriptionTier::query()->create([
            'community_id' => $community->getKey(),
            'name' => 'Pro',
            'slug' => 'pro',
            'price_cents' => 2500,
            'currency' => 'USD',
            'billing_interval' => 'month',
            'metadata' => [],
        ]);

        $post = CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'visibility' => 'paid',
            'paywall_tier_id' => $tier->getKey(),
        ]);

        $this->assertFalse($service->checkAccess($community, $user, $post));

        CommunitySubscription::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'subscription_tier_id' => $tier->getKey(),
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_test',
            'status' => 'active',
            'renews_at' => CarbonImmutable::now()->addMonth(),
            'metadata' => [],
        ]);

        $this->assertTrue($service->checkAccess($community->fresh(), $user->fresh(), $post->fresh()));
    }

    public function test_has_entitlement_checks_grants_and_purchases(): void
    {
        /** @var CommunityPaywallService $service */
        $service = $this->app->make(CommunityPaywallService::class);

        $community = Community::factory()->create();
        $user = User::factory()->create();
        $tier = CommunitySubscriptionTier::query()->create([
            'community_id' => $community->getKey(),
            'name' => 'Annual',
            'slug' => 'annual',
            'price_cents' => 9900,
            'currency' => 'USD',
            'billing_interval' => 'year',
            'metadata' => [],
        ]);

        $this->assertFalse($service->hasEntitlement($community, $user, $tier->getKey()));

        $service->grantTemporaryAccess($community, $user, $tier->getKey(), CarbonImmutable::now()->addDays(3));

        $this->assertTrue($service->hasEntitlement($community->fresh(), $user->fresh(), $tier->getKey()));

        $access = CommunityPaywallAccess::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertTrue($access->access_ends_at->greaterThan(CarbonImmutable::now()));
    }

    public function test_grant_temporary_access_updates_existing_record(): void
    {
        /** @var CommunityPaywallService $service */
        $service = $this->app->make(CommunityPaywallService::class);

        $community = Community::factory()->create();
        $user = User::factory()->create();
        $tier = CommunitySubscriptionTier::query()->create([
            'community_id' => $community->getKey(),
            'name' => 'VIP',
            'slug' => 'vip',
            'price_cents' => 15000,
            'currency' => 'USD',
            'billing_interval' => 'month',
            'metadata' => [],
        ]);

        $expiresAt = CarbonImmutable::now()->addWeek();
        $service->grantTemporaryAccess($community, $user, $tier->getKey(), $expiresAt, $community->created_by);
        $service->grantTemporaryAccess($community, $user, $tier->getKey(), $expiresAt->addDays(2), $community->created_by);

        $access = CommunityPaywallAccess::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->where('subscription_tier_id', $tier->getKey())
            ->first();

        $this->assertNotNull($access);
        $this->assertSame($expiresAt->addDays(2)->toIso8601String(), $access->access_ends_at?->toIso8601String());
        $this->assertSame($community->created_by, $access->granted_by);
    }
}
