<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\ProfileActivity;
use App\Domain\Search\SearchSyncManager;
use App\Domain\Communities\Services\ProfileActivityMigrationService;
use App\Models\Certificate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use App\Support\Observability\ObservabilityManager;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class ProfileActivityMigrationServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_migrate_projects_posts_comments_and_completions(): void
    {
        $user = User::factory()->create();
        $courseId = 551;

        $mockSyncManager = Mockery::mock(SearchSyncManager::class);
        $mockSyncManager->shouldReceive('queueModelSync')->andReturnNull();
        $mockSyncManager->shouldReceive('queueModelDeletion')->andReturnNull();
        $this->app->instance(SearchSyncManager::class, $mockSyncManager);

        $observabilityMock = Mockery::mock(ObservabilityManager::class);
        $observabilityMock->shouldReceive('recordQueueJob')->andReturnNull();
        $observabilityMock->shouldReceive('recordQueueLag')->andReturnNull();
        $observabilityMock->shouldReceive('recordQueueFailure')->andReturnNull();
        $this->app->instance(ObservabilityManager::class, $observabilityMock);

        config(['search.sync.resources' => []]);
        Bus::fake();
        Queue::fake();
        Community::flushEventListeners();
        CommunityPost::flushEventListeners();
        CommunityPostComment::flushEventListeners();
        Event::forget(JobProcessed::class);
        Event::forget(JobProcessing::class);

        $community = Community::factory()->create([
            'settings' => [
                'classroom_links' => [
                    [
                        'course_id' => $courseId,
                        'default_role' => 'member',
                    ],
                ],
            ],
        ]);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'status' => 'active',
            'role' => 'member',
        ]);

        $publishedAt = CarbonImmutable::parse('2025-01-15 08:30:00');
        $post = CommunityPost::factory()
            ->for($community)
            ->for($user, 'author')
            ->create([
                'published_at' => $publishedAt,
                'created_at' => $publishedAt->subMinutes(5),
            ]);

        CommunityPostComment::factory()
            ->for($community)
            ->for($post, 'post')
            ->for($user, 'author')
            ->create([
                'created_at' => $publishedAt->addHour(),
            ]);

        $certificate = new Certificate();
        $certificate->forceFill([
            'user_id' => $user->getKey(),
            'course_id' => $courseId,
            'identifier' => Str::uuid()->toString(),
            'created_at' => $publishedAt->addHours(2),
            'updated_at' => $publishedAt->addHours(2),
        ])->save();

        /** @var ProfileActivityMigrationService $service */
        $service = $this->app->make(ProfileActivityMigrationService::class);

        $report = $service->migrate(dryRun: false, chunkSize: 50);

        $this->assertSame(1, $report->postsProcessed);
        $this->assertSame(1, $report->commentsProcessed);
        $this->assertSame(1, $report->completionsProcessed);
        $this->assertSame(3, $report->recordsCreated);
        $this->assertSame(0, $report->recordsUpdated);
        $this->assertSame(0, $report->recordsSkipped);

        $this->assertDatabaseCount('profile_activities', 3);

        $postProjection = ProfileActivity::query()
            ->where('subject_type', 'community_post')
            ->first();
        $this->assertNotNull($postProjection);
        $this->assertTrue($postProjection->occurred_at->equalTo($publishedAt));

        $service->migrate(dryRun: false, chunkSize: 50);

        $this->assertDatabaseCount('profile_activities', 3);
        $this->assertSame(3, ProfileActivity::query()->count());
        $this->assertSame(
            [3, 3],
            [
                ProfileActivity::query()->count(),
                ProfileActivity::query()->distinct('idempotency_key')->count(),
            ]
        );
    }
}
