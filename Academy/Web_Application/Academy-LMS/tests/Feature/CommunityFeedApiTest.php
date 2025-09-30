<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityFeedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 20, 10));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_member_can_fetch_paginated_feed(): void
    {
        [$community, $member] = $this->seedCommunityWithPosts();

        Sanctum::actingAs($member->user);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/feed?per_page=10");

        $response->assertOk();

        $payload = $response->json();
        $this->assertSame($community->getKey(), $payload['meta']['community_id']);
        $this->assertSame('new', $payload['meta']['filter']);
        $this->assertCount(2, $payload['data']);
        $this->assertArrayHasKey('attachments', $payload['data'][0]);
        $this->assertFalse($payload['data'][0]['is_archived']);
    }

    public function test_archived_posts_are_not_returned_in_feed(): void
    {
        [$community, $member] = $this->seedCommunityWithPosts(archiveLatest: true);
        Sanctum::actingAs($member->user);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/feed");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_member_can_fetch_pinned_posts(): void
    {
        [$community, $member] = $this->seedCommunityWithPosts(pinFirst: true);
        Sanctum::actingAs($member->user);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/feed/pinned");

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.pinned'));
        $this->assertTrue($response->json('data.pinned.0.is_archived') === false);
    }

    private function seedCommunityWithPosts(
        bool $archiveLatest = false,
        bool $pinFirst = false
    ): array {
        $user = User::factory()->create(['role' => 'student']);

        $community = Community::create([
            'slug' => 'growth-guild-' . uniqid(),
            'name' => 'Growth Guild',
            'visibility' => 'public',
            'join_policy' => 'open',
            'default_post_visibility' => 'community',
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
            'launched_at' => CarbonImmutable::now()->subMonth(),
        ]);

        $member = CommunityMember::create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subMonth(),
            'last_seen_at' => CarbonImmutable::now()->subDay(),
        ]);

        $firstPost = CommunityPost::create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'type' => 'text',
            'body_md' => 'Welcome to the guild',
            'body_html' => '<p>Welcome to the guild</p>',
            'visibility' => 'community',
            'is_pinned' => $pinFirst,
            'published_at' => CarbonImmutable::now()->subDays(2),
        ]);

        $secondPost = CommunityPost::create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'type' => 'text',
            'body_md' => 'Weekly wins',
            'body_html' => '<p>Weekly wins</p>',
            'visibility' => 'community',
            'published_at' => CarbonImmutable::now()->subDay(),
        ]);

        if ($archiveLatest) {
            $secondPost->markArchived('test', CarbonImmutable::now()->subDay());
        }

        return [$community, $member];
    }
}
