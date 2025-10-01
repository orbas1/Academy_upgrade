<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\ProfileActivity;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityMigrateLegacyActivityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_migrates_posts_comments_and_completions(): void
    {
        $community = Community::factory()->create([
            'settings' => [
                'classroom_links' => [
                    202 => [
                        'course_id' => 202,
                        'default_role' => 'student',
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create();

        $post = CommunityPost::factory()->create([
            'community_id' => $community->getKey(),
            'author_id' => $user->getKey(),
            'published_at' => now()->subDay(),
            'type' => 'text',
            'visibility' => 'community',
        ]);

        $comment = CommunityPostComment::factory()->create([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'author_id' => $user->getKey(),
            'created_at' => now()->subHours(12),
        ]);

        Certificate::forceCreate([
            'user_id' => $user->getKey(),
            'course_id' => 202,
            'identifier' => 'CERT-123',
            'created_at' => now()->subHours(6),
        ]);

        $this->artisan('community:migrate-legacy-activity', ['--chunk' => 100])
            ->assertExitCode(0);

        $this->assertDatabaseHas('profile_activities', [
            'subject_type' => 'community_post',
            'subject_id' => $post->getKey(),
        ]);

        $this->assertDatabaseHas('profile_activities', [
            'subject_type' => 'community_comment',
            'subject_id' => $comment->getKey(),
        ]);

        $this->assertDatabaseHas('profile_activities', [
            'subject_type' => 'certificate',
            'subject_id' => Certificate::first()->getKey(),
        ]);

        $count = ProfileActivity::count();

        $this->artisan('community:migrate-legacy-activity', ['--chunk' => 100])
            ->assertExitCode(0);

        $this->assertSame($count, ProfileActivity::count());
    }
}
