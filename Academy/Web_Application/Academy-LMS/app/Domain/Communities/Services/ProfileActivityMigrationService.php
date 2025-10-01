<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\DTO\ProfileActivityMigrationReport;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\ProfileActivity;
use App\Models\Certificate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfileActivityMigrationService
{
    public function __construct(private readonly CommunityCourseLinkResolver $linkResolver)
    {
    }

    public function migrate(bool $dryRun = false, int $chunkSize = 500, ?Carbon $since = null): ProfileActivityMigrationReport
    {
        $postsProcessed = 0;
        $commentsProcessed = 0;
        $completionsProcessed = 0;
        $recordsCreated = 0;
        $recordsUpdated = 0;
        $recordsSkipped = 0;

        $postsQuery = CommunityPost::query()
            ->select(['id', 'community_id', 'author_id', 'type', 'visibility', 'published_at', 'created_at'])
            ->whereNull('deleted_at')
            ->orderBy('id');

        if ($since) {
            $postsQuery->where(function ($query) use ($since) {
                $query->where('published_at', '>=', $since)
                    ->orWhere('created_at', '>=', $since);
            });
        }

        $postsQuery->chunkById($chunkSize, function (Collection $chunk) use (
            $dryRun,
            &$postsProcessed,
            &$recordsCreated,
            &$recordsUpdated,
            &$recordsSkipped
        ): void {
            $this->withTransaction(function () use (
                $chunk,
                $dryRun,
                &$postsProcessed,
                &$recordsCreated,
                &$recordsUpdated,
                &$recordsSkipped
            ): void {
                foreach ($chunk as $post) {
                    ++$postsProcessed;
                    $occurredAt = $this->resolveTimestamp($post->published_at, $post->created_at);
                    if (! $occurredAt) {
                        ++$recordsSkipped;
                        continue;
                    }

                    $payload = [
                        'activity_type' => 'community_post.published',
                        'subject_type' => 'community_post',
                        'subject_id' => (int) $post->getKey(),
                        'occurred_at' => $occurredAt,
                        'context' => [
                            'type' => $post->type,
                            'visibility' => $post->visibility,
                        ],
                    ];

                    $result = $this->storeActivity(
                        userId: (int) $post->author_id,
                        communityId: (int) $post->community_id,
                        payload: $payload,
                        idempotencyKey: $this->idempotencyKey('community_post', (int) $post->getKey(), (int) $post->community_id),
                        dryRun: $dryRun
                    );

                    $recordsCreated += $result['created'];
                    $recordsUpdated += $result['updated'];
                    $recordsSkipped += $result['skipped'];
                }
            });
        });

        $commentsQuery = CommunityPostComment::query()
            ->select(['id', 'community_id', 'post_id', 'author_id', 'created_at'])
            ->whereNull('deleted_at')
            ->orderBy('id');

        if ($since) {
            $commentsQuery->where('created_at', '>=', $since);
        }

        $commentsQuery->chunkById($chunkSize, function (Collection $chunk) use (
            $dryRun,
            &$commentsProcessed,
            &$recordsCreated,
            &$recordsUpdated,
            &$recordsSkipped
        ): void {
            $this->withTransaction(function () use (
                $chunk,
                $dryRun,
                &$commentsProcessed,
                &$recordsCreated,
                &$recordsUpdated,
                &$recordsSkipped
            ): void {
                foreach ($chunk as $comment) {
                    ++$commentsProcessed;
                    $occurredAt = $this->resolveTimestamp($comment->created_at, $comment->created_at);
                    if (! $occurredAt) {
                        ++$recordsSkipped;
                        continue;
                    }

                    $payload = [
                        'activity_type' => 'community_comment.posted',
                        'subject_type' => 'community_comment',
                        'subject_id' => (int) $comment->getKey(),
                        'occurred_at' => $occurredAt,
                        'context' => [
                            'post_id' => (int) $comment->post_id,
                        ],
                    ];

                    $result = $this->storeActivity(
                        userId: (int) $comment->author_id,
                        communityId: (int) $comment->community_id,
                        payload: $payload,
                        idempotencyKey: $this->idempotencyKey('community_comment', (int) $comment->getKey(), (int) $comment->community_id),
                        dryRun: $dryRun
                    );

                    $recordsCreated += $result['created'];
                    $recordsUpdated += $result['updated'];
                    $recordsSkipped += $result['skipped'];
                }
            });
        });

        $courseMap = $this->linkResolver->buildCourseCommunityMap();

        $certificateQuery = Certificate::query()
            ->select(['id', 'user_id', 'course_id', 'identifier', 'created_at'])
            ->whereNotNull('user_id')
            ->orderBy('id');

        if ($since) {
            $certificateQuery->where('created_at', '>=', $since);
        }

        $certificateQuery->chunkById($chunkSize, function (Collection $chunk) use (
            $courseMap,
            $dryRun,
            &$completionsProcessed,
            &$recordsCreated,
            &$recordsUpdated,
            &$recordsSkipped
        ): void {
            $this->withTransaction(function () use (
                $chunk,
                $courseMap,
                $dryRun,
                &$completionsProcessed,
                &$recordsCreated,
                &$recordsUpdated,
                &$recordsSkipped
            ): void {
                foreach ($chunk as $certificate) {
                    ++$completionsProcessed;

                    $occurredAt = $this->resolveTimestamp($certificate->created_at, $certificate->created_at);
                    if (! $occurredAt) {
                        ++$recordsSkipped;
                        continue;
                    }

                    $courseId = (int) $certificate->course_id;
                    $communities = $courseMap[$courseId] ?? [];

                    if ($communities === []) {
                        $payload = [
                            'activity_type' => 'course.completed',
                            'subject_type' => 'certificate',
                            'subject_id' => (int) $certificate->getKey(),
                            'occurred_at' => $occurredAt,
                            'context' => [
                                'course_id' => $courseId,
                                'certificate_identifier' => $certificate->identifier,
                            ],
                        ];

                        $result = $this->storeActivity(
                            userId: (int) $certificate->user_id,
                            communityId: null,
                            payload: $payload,
                            idempotencyKey: $this->idempotencyKey('course_completion', (int) $certificate->getKey(), null),
                            dryRun: $dryRun
                        );

                        $recordsCreated += $result['created'];
                        $recordsUpdated += $result['updated'];
                        $recordsSkipped += $result['skipped'];
                        continue;
                    }

                    foreach ($communities as $communityId => $link) {
                        $payload = [
                            'activity_type' => 'course.completed',
                            'subject_type' => 'certificate',
                            'subject_id' => (int) $certificate->getKey(),
                            'occurred_at' => $occurredAt,
                            'context' => [
                                'course_id' => $courseId,
                                'certificate_identifier' => $certificate->identifier,
                                'community_id' => $communityId,
                            ],
                        ];

                        $result = $this->storeActivity(
                            userId: (int) $certificate->user_id,
                            communityId: (int) $communityId,
                            payload: $payload,
                            idempotencyKey: $this->idempotencyKey('course_completion', (int) $certificate->getKey(), (int) $communityId),
                            dryRun: $dryRun
                        );

                        $recordsCreated += $result['created'];
                        $recordsUpdated += $result['updated'];
                        $recordsSkipped += $result['skipped'];
                    }
                }
            });
        });

        return new ProfileActivityMigrationReport(
            postsProcessed: $postsProcessed,
            commentsProcessed: $commentsProcessed,
            completionsProcessed: $completionsProcessed,
            recordsCreated: $recordsCreated,
            recordsUpdated: $recordsUpdated,
            recordsSkipped: $recordsSkipped,
        );
    }

    private function idempotencyKey(string $type, int $subjectId, ?int $communityId): string
    {
        return sha1("profile:{$type}:{$subjectId}:" . ($communityId ?? 'global'));
    }

    private function resolveTimestamp(mixed $preferred, mixed $fallback): ?Carbon
    {
        foreach ([$preferred, $fallback] as $value) {
            if ($value instanceof Carbon) {
                return $value;
            }

            if (is_string($value) && $value !== '') {
                try {
                    return Carbon::parse($value);
                } catch (Throwable $exception) {
                    Log::warning('Failed to parse timestamp for profile activity', [
                        'value' => $value,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{created: int, updated: int, skipped: int}
     */
    private function storeActivity(int $userId, ?int $communityId, array $payload, string $idempotencyKey, bool $dryRun): array
    {
        if ($userId <= 0) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 1];
        }

        $attributes = [
            'user_id' => $userId,
            'community_id' => $communityId,
            'activity_type' => $payload['activity_type'],
            'subject_type' => $payload['subject_type'],
            'subject_id' => $payload['subject_id'],
        ];

        if ($dryRun) {
            $exists = ProfileActivity::query()
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            return [
                'created' => $exists ? 0 : 1,
                'updated' => $exists ? 1 : 0,
                'skipped' => 0,
            ];
        }

        /** @var ProfileActivity $activity */
        $activity = ProfileActivity::query()->firstOrNew([
            'idempotency_key' => $idempotencyKey,
        ]);

        $activity->fill($attributes + [
            'occurred_at' => $payload['occurred_at'],
            'context' => $payload['context'] ?? [],
        ]);

        $wasExisting = $activity->exists;
        $activity->save();

        return [
            'created' => $wasExisting ? 0 : 1,
            'updated' => $wasExisting ? 1 : 0,
            'skipped' => 0,
        ];
    }

    private function withTransaction(callable $callback): void
    {
        try {
            DB::transaction($callback, 3);
        } catch (QueryException $exception) {
            throw $exception;
        }
    }
}
