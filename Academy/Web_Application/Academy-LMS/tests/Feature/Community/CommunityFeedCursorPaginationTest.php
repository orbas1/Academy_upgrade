<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityFeedCursorPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 22, 11));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_feed_endpoint_returns_cursor_pagination_metadata(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $community = Community::factory()->create([
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => CarbonImmutable::now()->subMinutes(5),
        ]);
        CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => CarbonImmutable::now()->subMinutes(3),
        ]);
        CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => CarbonImmutable::now()->subMinute(),
        ]);

        Sanctum::actingAs($user);

        $firstPage = $this->getJson("/api/v1/communities/{$community->getKey()}/feed?page_size=1");

        $firstPage->assertOk();
        $firstPage->assertJsonPath('meta.community_id', $community->getKey());
        $firstPage->assertJsonCount(1, 'data');
        $firstPage->assertJsonStructure(['meta' => ['pagination' => ['next_cursor']]]);

        $cursor = $firstPage->json('meta.pagination.next_cursor');
        $this->assertNotEmpty($cursor);

        $secondPage = $this->getJson("/api/v1/communities/{$community->getKey()}/feed?page_size=1&cursor={$cursor}");
        $secondPage->assertOk();
        $secondPage->assertJsonCount(1, 'data');
        $this->assertNotEquals(
            $firstPage->json('data.0.id'),
            $secondPage->json('data.0.id')
        );
    }
}
