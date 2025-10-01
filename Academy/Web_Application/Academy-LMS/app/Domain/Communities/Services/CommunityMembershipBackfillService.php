<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\DTO\MembershipBackfillReport;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommunityMembershipBackfillService
{
    public function __construct(private readonly CommunityCourseLinkResolver $linkResolver)
    {
    }

    public function backfillFromClassrooms(
        ?Community $specificCommunity = null,
        int $batchSize = 1000,
        bool $dryRun = false,
        ?callable $progress = null
    ): MembershipBackfillReport {
        $communitiesQuery = Community::query()->select(['id', 'settings']);

        if ($specificCommunity) {
            $communitiesQuery->whereKey($specificCommunity->getKey());
        }

        /** @var Collection<int, Community> $communities */
        $communities = $communitiesQuery->get();

        $communitiesProcessed = 0;
        $enrollmentsScanned = 0;
        $membersCreated = 0;
        $membersReactivated = 0;
        $membersUpdated = 0;
        $recordsSkipped = 0;

        foreach ($communities as $community) {
            $links = $this->linkResolver->extractLinks($community);
            if ($links === []) {
                if ($progress) {
                    $progress($community, [
                        'status' => 'skipped',
                        'reason' => 'no_linked_courses',
                    ]);
                }
                continue;
            }

            $communitiesProcessed++;

            $courseIds = array_keys($links);

            $enrollmentQuery = Enrollment::query()
                ->select(['id', 'user_id', 'course_id', 'entry_date'])
                ->whereIn('course_id', $courseIds)
                ->orderBy('id');

            $enrollmentQuery->chunkById($batchSize, function (Collection $chunk) use (
                $community,
                $links,
                $dryRun,
                &$enrollmentsScanned,
                &$membersCreated,
                &$membersUpdated,
                &$membersReactivated,
                &$recordsSkipped,
            ): void {
                if ($chunk->isEmpty()) {
                    return;
                }

                $userIds = $chunk->pluck('user_id')->filter()->map(static fn ($id) => (int) $id)->unique()->values();
                if ($userIds->isEmpty()) {
                    $recordsSkipped += $chunk->count();
                    return;
                }

                /** @var Collection<int, CommunityMember> $existingMembers */
                $existingMembers = CommunityMember::withTrashed()
                    ->where('community_id', $community->getKey())
                    ->whereIn('user_id', $userIds)
                    ->get()
                    ->keyBy('user_id');

                $operation = function () use (
                    $chunk,
                    $links,
                    $community,
                    $dryRun,
                    &$enrollmentsScanned,
                    &$membersCreated,
                    &$membersUpdated,
                    &$membersReactivated,
                    &$recordsSkipped,
                    $existingMembers
                ): void {
                    foreach ($chunk as $enrollment) {
                        ++$enrollmentsScanned;

                        $userId = (int) $enrollment->user_id;
                        if ($userId <= 0) {
                            ++$recordsSkipped;
                            continue;
                        }

                        $courseId = (int) $enrollment->course_id;
                        $link = $links[$courseId] ?? null;
                        if (! $link) {
                            ++$recordsSkipped;
                            continue;
                        }

                        $idempotencyKey = $this->idempotencyKey($community->getKey(), $userId, $courseId);
                        $joinedAt = $this->resolveJoinedAt($enrollment->entry_date);
                        $role = $link['default_role'] ?? 'member';

                        /** @var CommunityMember|null $member */
                        $member = $existingMembers->get($userId);

                        if ($dryRun) {
                            if (! $member) {
                                ++$membersCreated;
                            } elseif ($member->trashed() || $member->status !== 'active') {
                                ++$membersReactivated;
                            } elseif ($this->shouldUpgradeRole($member->role, $role)) {
                                ++$membersUpdated;
                            }
                            continue;
                        }

                        if (! $member) {
                            $metadata = $this->buildMetadata(null, $link, $idempotencyKey);

                            CommunityMember::create([
                                'community_id' => $community->getKey(),
                                'user_id' => $userId,
                                'role' => $role,
                                'status' => 'active',
                                'joined_at' => $joinedAt,
                                'metadata' => $metadata,
                            ]);

                            ++$membersCreated;
                            continue;
                        }

                        $originalJoined = $member->joined_at ? Carbon::parse($member->joined_at) : null;
                        $newJoinedAt = $originalJoined ? $originalJoined->min($joinedAt) : $joinedAt;
                        $metadata = $this->buildMetadata($member->metadata, $link, $idempotencyKey);

                        $changes = [
                            'metadata' => $metadata,
                        ];

                        $reactivated = false;
                        if ($member->trashed()) {
                            $member->restore();
                            $member->refresh();
                            $reactivated = true;
                        }

                        if ($member->status !== 'active') {
                            $changes['status'] = 'active';
                            $reactivated = true;
                        }

                        $roleChanged = false;
                        if ($this->shouldUpgradeRole($member->role, $role)) {
                            $changes['role'] = $role;
                            $roleChanged = true;
                        }

                        $joinedChanged = false;
                        if ($member->joined_at === null || $member->joined_at->gt($newJoinedAt)) {
                            $changes['joined_at'] = $newJoinedAt;
                            $joinedChanged = true;
                        }

                        $metadataChanged = false;
                        $dirty = false;
                        foreach ($changes as $column => $value) {
                            if ($column === 'metadata') {
                                if ($this->metadataDiffers($member->metadata ?? [], $value)) {
                                    $member->{$column} = $value;
                                    $dirty = true;
                                    $metadataChanged = true;
                                }
                                continue;
                            }

                            if ($member->{$column} != $value) {
                                $member->{$column} = $value;
                                $dirty = true;
                            }
                        }

                        if ($reactivated) {
                            ++$membersReactivated;
                        }

                        if ($dirty) {
                            $originalRole = $member->getOriginal('role');
                            $member->save();

                            if ($roleChanged && $originalRole !== $role) {
                                ++$membersUpdated;
                            } elseif ($metadataChanged || $joinedChanged) {
                                ++$membersUpdated;
                            }
                        }
                    }
                };

                $this->withRetries($operation);
            });

            if ($progress) {
                $progress($community, [
                    'status' => 'processed',
                ]);
            }
        }

        return new MembershipBackfillReport(
            communitiesProcessed: $communitiesProcessed,
            enrollmentsScanned: $enrollmentsScanned,
            membersCreated: $membersCreated,
            membersReactivated: $membersReactivated,
            membersUpdated: $membersUpdated,
            recordsSkipped: $recordsSkipped,
        );
    }

    private function idempotencyKey(int $communityId, int $userId, int $courseId): string
    {
        return sha1("classrooms:{$communityId}:{$userId}:{$courseId}");
    }

    private function resolveJoinedAt(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (Throwable $exception) {
                Log::warning('Unable to parse enrollment entry date', [
                    'value' => $value,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return Carbon::now();
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>  $link
     * @return array<string, mixed>
     */
    private function buildMetadata(?array $existing, array $link, string $idempotencyKey): array
    {
        $metadata = is_array($existing) ? $existing : [];

        $backfill = Arr::get($metadata, 'backfill');
        if (! is_array($backfill)) {
            $backfill = [];
        }

        $backfill['classrooms'] = array_filter([
            'course_id' => $link['course_id'] ?? null,
            'source' => 'classrooms',
            'idempotency_key' => $idempotencyKey,
            'synced_at' => Carbon::now()->toIso8601String(),
        ]);

        $metadata['backfill'] = $backfill;

        return $metadata;
    }

    private function shouldUpgradeRole(?string $current, string $incoming): bool
    {
        $priority = [
            'member' => 1,
            'moderator' => 2,
            'admin' => 3,
            'owner' => 4,
        ];

        $currentRank = $priority[$current ?? 'member'] ?? 1;
        $incomingRank = $priority[$incoming] ?? 1;

        return $incomingRank > $currentRank;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     */
    private function metadataDiffers(array $current, array $incoming): bool
    {
        ksort($current);
        ksort($incoming);

        return $current !== $incoming;
    }

    private function withRetries(callable $operation, int $attempts = 3, int $sleepMilliseconds = 250): void
    {
        $attempt = 0;
        beginning:
        try {
            ++$attempt;
            DB::transaction(static function () use ($operation): void {
                $operation();
            }, 3);
        } catch (QueryException $exception) {
            if ($attempt < $attempts) {
                usleep($sleepMilliseconds * 1000);
                goto beginning;
            }

            throw $exception;
        }
    }
}
