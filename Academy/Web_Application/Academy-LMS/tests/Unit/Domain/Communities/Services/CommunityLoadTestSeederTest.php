<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\ProfileActivity;
use App\Domain\Communities\Services\CommunityLoadTestSeeder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

final class CommunityLoadTestSeederTest extends TestCase
{
    use DatabaseMigrations;

    public function test_seed_generates_expected_entities_and_credentials(): void
    {
        CarbonImmutable::setTestNow('2025-03-01 10:00:00');

        /** @var CommunityLoadTestSeeder $seeder */
        $seeder = $this->app->make(CommunityLoadTestSeeder::class);

        $summary = $seeder->seed([
            'community_count' => 1,
            'members_per_community' => 3,
            'posts_per_member' => 2,
            'comments_per_post' => 2,
            'reactions_per_post' => 2,
            'points_events_per_member' => 2,
            'tokens_per_community' => 2,
            'seed_profile_activity' => true,
            'owner_password' => 'Owner#Load123!',
            'member_password' => 'Member#Load123!',
        ]);

        $this->assertSame(1, $summary->communities);
        $this->assertSame(4, $summary->members); // 3 members + 1 owner
        $this->assertSame(6, $summary->posts);
        $this->assertSame(12, $summary->comments);
        $this->assertSame(12, $summary->reactions);
        $this->assertSame(6, $summary->pointsEvents);
        $this->assertSame(18, $summary->profileActivities);

        $credentials = $summary->credentials['communities'] ?? [];
        $this->assertCount(1, $credentials);
        $this->assertArrayHasKey('owner', $credentials[0]);
        $this->assertArrayHasKey('members', $credentials[0]);
        $this->assertCount(2, $credentials[0]['members']);
        $this->assertMatchesRegularExpression('/^load-owner-1-/', $credentials[0]['owner']['email']);
        $this->assertSame('Owner#Load123!', $credentials[0]['owner']['password']);
        $this->assertNotEmpty($credentials[0]['owner']['api_token']);

        $this->assertDatabaseCount('communities', 1);
        $this->assertDatabaseCount('community_members', 4);
        $this->assertDatabaseCount('community_posts', 6);
        $this->assertDatabaseCount('community_post_comments', 12);
        $this->assertDatabaseCount('community_post_likes', 12);
        $this->assertDatabaseCount('community_points_ledger', 6);
        $this->assertDatabaseCount('profile_activities', 18);

        $post = CommunityPost::query()->firstOrFail();
        $this->assertGreaterThanOrEqual(1, $post->comment_count);
        $this->assertGreaterThanOrEqual(1, $post->like_count);

        $comment = CommunityPostComment::query()->firstOrFail();
        $this->assertNotNull($comment->body_md);

        $activity = ProfileActivity::query()->firstOrFail();
        $this->assertNotNull($activity->occurred_at);
        $this->assertSame('community_post.published', $activity->activity_type);
    }
}
