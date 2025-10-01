<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Services\CommunityMembershipBackfillService;
use App\Domain\Search\SearchSyncManager;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use App\Support\Observability\ObservabilityManager;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

final class CommunityMembershipBackfillServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_backfill_creates_and_reactivates_memberships(): void
    {
        $courseId = 8821;
        $user = User::factory()->create();

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
        CommunityMember::flushEventListeners();
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

        $entryDate = CarbonImmutable::parse('2025-02-01 09:15:00');
        Enrollment::query()->create([
            'user_id' => $user->getKey(),
            'course_id' => $courseId,
            'enrollment_type' => 'manual',
            'entry_date' => $entryDate->timestamp,
        ]);

        /** @var CommunityMembershipBackfillService $service */
        $service = $this->app->make(CommunityMembershipBackfillService::class);

        $report = $service->backfillFromClassrooms($community, batchSize: 25);

        $this->assertSame(1, $report->communitiesProcessed);
        $this->assertSame(1, $report->enrollmentsScanned);
        $this->assertSame(1, $report->membersCreated);
        $this->assertSame(0, $report->membersReactivated);
        $this->assertSame(0, $report->membersUpdated);

        $member = CommunityMember::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        $this->assertNotNull($member);
        $this->assertFalse($member->trashed());
        $this->assertSame('active', $member->status);
        $this->assertSame('member', $member->role);
        $this->assertEqualsWithDelta($entryDate->timestamp, $member->joined_at?->getTimestamp(), 21600);
        $this->assertSame(
            $courseId,
            $member->metadata['backfill']['classrooms']['course_id'] ?? null
        );
        $this->assertNotEmpty($member->metadata['backfill']['classrooms']['idempotency_key']);

        // Force downgrade + soft delete to validate reactivation and role upgrades.
        $member->forceFill([
            'status' => 'pending',
            'role' => 'member',
            'joined_at' => $entryDate->addDay(),
        ])->save();
        $member->delete();

        $community->forceFill([
            'settings' => [
                'classroom_links' => [
                    [
                        'course_id' => $courseId,
                        'default_role' => 'admin',
                    ],
                ],
            ],
        ])->save();

        $secondReport = $service->backfillFromClassrooms($community, batchSize: 25);

        $member->refresh();

        $this->assertFalse($member->trashed());
        $this->assertSame('active', $member->status);
        $this->assertSame('admin', $member->role);
        $this->assertEqualsWithDelta($entryDate->timestamp, $member->joined_at?->getTimestamp(), 21600);
        $this->assertArrayHasKey('synced_at', $member->metadata['backfill']['classrooms']);

        $this->assertSame(1, $secondReport->membersReactivated);
        $this->assertSame(1, $secondReport->membersUpdated);
        $this->assertSame(0, $secondReport->membersCreated);

        $idempotencyKey = $member->metadata['backfill']['classrooms']['idempotency_key'];
        $this->assertSame(
            $idempotencyKey,
            DB::table('community_members')
                ->where('id', $member->getKey())
                ->value('metadata->backfill->classrooms->idempotency_key')
        );
    }
}
