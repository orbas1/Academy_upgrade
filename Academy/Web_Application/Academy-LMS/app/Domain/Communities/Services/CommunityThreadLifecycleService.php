<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Events\CommunityThreadArchived;
use App\Domain\Communities\Events\CommunityThreadReactivated;
use App\Domain\Communities\Models\CommunityPost;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Exception as QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityThreadLifecycleService
{
    public function archiveInactiveThreads(?int $communityId = null, bool $dryRun = false, ?int $chunkSize = null): array
    {
        $now = CarbonImmutable::now();
        $inactiveDays = (int) config('communities.automation.auto_archive.inactive_days', 45);
        $recentActivityDays = (int) config('communities.automation.auto_archive.recent_activity_days', 7);
        $chunk = $chunkSize ?? (int) config('communities.automation.auto_archive.chunk', 500);

        $inactiveBefore = $now->subDays($inactiveDays);
        $recentActivityCutoff = $now->subDays($recentActivityDays);

        $query = CommunityPost::query()
            ->where('is_archived', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $inactiveBefore)
            ->where(function ($builder) use ($recentActivityCutoff) {
                $builder->where('comment_count', 0)
                    ->orWhereNotExists(function ($sub) use ($recentActivityCutoff) {
                        $sub->select(DB::raw('1'))
                            ->from('community_post_comments')
                            ->whereColumn('community_post_comments.post_id', 'community_posts.id')
                            ->whereNull('community_post_comments.deleted_at')
                            ->where('community_post_comments.created_at', '>=', $recentActivityCutoff);
                    });
            })
            ->where(function ($builder) use ($recentActivityCutoff) {
                $builder->whereNull('updated_at')
                    ->orWhere('updated_at', '<=', $recentActivityCutoff);
            })
            ->orderBy('id');

        if ($communityId !== null) {
            $query->where('community_id', $communityId);
        }

        $candidates = (clone $query)->count('id');

        if ($dryRun) {
            return [
                'archived' => 0,
                'candidates' => $candidates,
                'inactive_threshold' => $inactiveBefore->toIso8601String(),
                'recent_activity_cutoff' => $recentActivityCutoff->toIso8601String(),
            ];
        }

        $archived = 0;

        try {
            $query->chunkById($chunk, function (EloquentCollection $posts) use (&$archived, $now, $inactiveBefore, $recentActivityCutoff) {
                foreach ($posts as $post) {
                    $context = [
                        'inactive_threshold' => $inactiveBefore->toIso8601String(),
                        'recent_activity_cutoff' => $recentActivityCutoff->toIso8601String(),
                    ];

                    $post->markArchived('auto_inactive', $now, $context);
                    $archived++;

                    event(new CommunityThreadArchived($post->fresh(), 'auto_inactive', $context));
                }
            });
        } catch (QueryException $exception) {
            Log::error('Failed to archive inactive community threads', [
                'message' => $exception->getMessage(),
                'community_id' => $communityId,
            ]);
        }

        return [
            'archived' => $archived,
            'candidates' => $candidates,
            'inactive_threshold' => $inactiveBefore->toIso8601String(),
            'recent_activity_cutoff' => $recentActivityCutoff->toIso8601String(),
        ];
    }

    public function markPostActive(CommunityPost $post, string $reason = 'activity'): void
    {
        if (! $post->is_archived) {
            return;
        }

        $now = CarbonImmutable::now();

        DB::transaction(function () use ($post, $now, $reason) {
            $post->markActive($now, $reason);
            event(new CommunityThreadReactivated($post->fresh(), $reason));
        });
    }
}
