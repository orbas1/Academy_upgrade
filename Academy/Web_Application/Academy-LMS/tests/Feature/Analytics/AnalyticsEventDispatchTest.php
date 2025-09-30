<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Services\CommunityPostService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsEventDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_creation_records_analytics_event(): void
    {
        $this->freezeTime();

        /** @var User $user */
        $user = User::factory()->create([
            'analytics_consent_at' => CarbonImmutable::now(),
            'analytics_consent_version' => config('analytics.consent.version'),
        ]);

        /** @var Community $community */
        $community = Community::factory()->create();

        $community->members()->create([
            'user_id' => $user->getKey(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subDay(),
        ]);

        /** @var CommunityPostService $service */
        $service = $this->app->make(CommunityPostService::class);
        $service->compose($community, $user, [
            'body_md' => 'Hello world',
            'visibility' => 'community',
        ]);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'post_create',
            'user_id' => $user->getKey(),
            'community_id' => $community->getKey(),
        ]);
    }

    public function test_consent_revocation_prevents_event_recording(): void
    {
        $this->freezeTime();

        /** @var User $user */
        $user = User::factory()->create([
            'analytics_consent_at' => null,
            'analytics_consent_version' => null,
        ]);

        /** @var Community $community */
        $community = Community::factory()->create();

        $community->members()->create([
            'user_id' => $user->getKey(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subDay(),
        ]);

        /** @var CommunityPostService $service */
        $service = $this->app->make(CommunityPostService::class);
        $service->compose($community, $user, [
            'body_md' => 'This should not emit analytics',
        ]);

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    public function test_analytics_consent_endpoint_updates_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'analytics_consent_at' => null,
            'analytics_consent_version' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/analytics-consent', [
            'granted' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'granted' => true,
                'version' => config('analytics.consent.version'),
            ]);

        $user->refresh();

        $this->assertNotNull($user->analytics_consent_at);
        $this->assertSame(config('analytics.consent.version'), $user->analytics_consent_version);

        $response = $this->postJson('/api/v1/me/analytics-consent', [
            'granted' => false,
        ]);

        $response->assertOk()
            ->assertJson([
                'granted' => false,
            ]);

        $user->refresh();

        $this->assertNotNull($user->analytics_consent_revoked_at);
    }
}
