<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CommunitiesAutoArchiveCommand;
use App\Domain\Communities\Events\CommunityThreadArchived;
use App\Domain\Communities\Events\CommunityThreadReactivated;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommunityAutoArchiveCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 20, 12));
        config()->set('communities.automation.auto_archive.inactive_days', 30);
        config()->set('communities.automation.auto_archive.recent_activity_days', 7);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_command_archives_inactive_threads(): void
    {
        Event::fake([CommunityThreadArchived::class]);

        [$community, $post] = $this->makeCommunityWithPost(
            publishedAt: CarbonImmutable::now()->subDays(45)
        );

        Artisan::call(CommunitiesAutoArchiveCommand::class);

        $post->refresh();

        $this->assertTrue($post->is_archived);
        $this->assertNotNull($post->archived_at);
        $this->assertEquals(CarbonImmutable::now()->toIso8601String(), $post->archived_at?->toIso8601String());

        Event::assertDispatched(CommunityThreadArchived::class, function (CommunityThreadArchived $event) use ($post) {
            return $event->post->is($post) && $event->reason === 'auto_inactive';
        });
    }

    public function test_comment_creation_reactivates_archived_post(): void
    {
        Event::fake([CommunityThreadReactivated::class]);

        [$community, $post, $member] = $this->makeCommunityWithPost(
            publishedAt: CarbonImmutable::now()->subDays(10),
            markArchived: true
        );

        CommunityPostComment::create([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'author_id' => $member->user_id,
            'body_md' => 'Nice update',
            'body_html' => '<p>Nice update</p>',
        ]);

        $post->refresh();

        $this->assertFalse($post->is_archived);
        $this->assertNull($post->archived_at);

        Event::assertDispatched(CommunityThreadReactivated::class, function (CommunityThreadReactivated $event) use ($post) {
            return $event->post->is($post) && $event->reason === 'comment_created';
        });
    }

    private function makeCommunityWithPost(
        ?CarbonImmutable $publishedAt = null,
        bool $markArchived = false
    ): array {
        $owner = User::factory()->create(['role' => 'student']);

        $community = Community::create([
            'slug' => 'builders-hub-' . uniqid(),
            'name' => 'Builders Hub',
            'visibility' => 'public',
            'join_policy' => 'open',
            'default_post_visibility' => 'community',
            'created_by' => $owner->getKey(),
            'updated_by' => $owner->getKey(),
            'launched_at' => CarbonImmutable::now()->subMonth(),
        ]);

        $member = CommunityMember::create([
            'community_id' => $community->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subMonth(),
            'last_seen_at' => CarbonImmutable::now()->subDay(),
        ]);

        $post = CommunityPost::create([
            'community_id' => $community->getKey(),
            'author_id' => $owner->getKey(),
            'type' => 'text',
            'body_md' => '# Update',
            'body_html' => '<h1>Update</h1>',
            'visibility' => 'community',
            'published_at' => $publishedAt ?? CarbonImmutable::now()->subDay(),
            'like_count' => 0,
            'comment_count' => 0,
        ]);

        if ($markArchived) {
            $post->markArchived('test_seed', CarbonImmutable::now()->subDay());
        }

        return [$community, $post, $member];
    }
}
