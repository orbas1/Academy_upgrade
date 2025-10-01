<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Profile;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\ProfileActivity;
use App\Http\Middleware\EnsureFeatureIsEnabled;
use App\Http\Middleware\WebConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileActivityFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure middleware is registered for direct route testing scenarios.
        Route::aliasMiddleware('feature.enabled', EnsureFeatureIsEnabled::class);
        $this->withoutMiddleware(WebConfig::class);
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        Config::set('app.url', 'http://localhost');
        Config::set('scout.driver', 'null');
        Config::set('app.timezone', 'UTC');
        date_default_timezone_set('UTC');
        URL::forceRootUrl('http://localhost');
        URL::forceScheme('http');
    }

    public function test_it_returns_paginated_activity_with_context(): void
    {
        $flags = config('feature-flags');
        $flags['community_profile_activity'] = true;
        Config::set('feature-flags', $flags);

        $user = User::factory()->create();
        $community = Community::factory()->create([
            'name' => 'Growth Lab',
            'slug' => 'growth-lab',
        ]);

        $first = ProfileActivity::factory()->create([
            'user_id' => $user->getKey(),
            'community_id' => $community->getKey(),
            'activity_type' => 'community_post.published',
            'subject_type' => 'community_post',
            'subject_id' => 501,
            'occurred_at' => now()->subMinutes(5),
            'context' => ['post_id' => 501, 'title' => 'Launch checklist'],
        ]);

        $second = ProfileActivity::factory()->create([
            'user_id' => $user->getKey(),
            'community_id' => $community->getKey(),
            'activity_type' => 'community_comment.created',
            'subject_type' => 'community_comment',
            'subject_id' => 777,
            'occurred_at' => now()->subMinutes(1),
            'context' => ['comment_id' => 777, 'excerpt' => 'Great idea!'],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me/profile-activity?per_page=1');

        $response->assertOk();
        $payload = $response->json();

        $this->assertSame(1, Arr::get($payload, 'meta.pagination.per_page'));
        $this->assertTrue(Arr::get($payload, 'meta.pagination.has_more', false));
        $this->assertSame($second->getKey(), Arr::get($payload, 'data.0.id'));
        $this->assertSame('community_comment.created', Arr::get($payload, 'data.0.activity_type'));
        $this->assertSame('Great idea!', Arr::get($payload, 'data.0.context.excerpt'));
        $this->assertSame('growth-lab', Arr::get($payload, 'data.0.community.slug'));

        $this->assertNotNull(Arr::get($payload, 'meta.pagination.next_cursor'));
    }

    public function test_it_can_filter_activity_by_community(): void
    {
        $flags = config('feature-flags');
        $flags['community_profile_activity'] = true;
        Config::set('feature-flags', $flags);

        $user = User::factory()->create();
        $communityA = Community::factory()->create();
        $communityB = Community::factory()->create();

        ProfileActivity::factory()->create([
            'user_id' => $user->getKey(),
            'community_id' => $communityA->getKey(),
            'activity_type' => 'community_post.published',
        ]);

        ProfileActivity::factory()->create([
            'user_id' => $user->getKey(),
            'community_id' => $communityB->getKey(),
            'activity_type' => 'community_comment.created',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(sprintf('/api/v1/me/profile-activity?community_id=%d', $communityB->getKey()));
        $response->assertOk();
        $payload = $response->json();

        $this->assertCount(1, $payload['data']);
        $this->assertSame($communityB->getKey(), Arr::get($payload, 'data.0.community.id'));
    }

    public function test_it_returns_not_found_when_feature_disabled(): void
    {
        $flags = config('feature-flags');
        $flags['community_profile_activity'] = false;
        Config::set('feature-flags', $flags);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/profile-activity')->assertNotFound();
    }

    public function test_it_requires_authentication(): void
    {
        $flags = config('feature-flags');
        $flags['community_profile_activity'] = true;
        Config::set('feature-flags', $flags);

        $this->getJson('/api/v1/me/profile-activity')->assertUnauthorized();
    }
}
