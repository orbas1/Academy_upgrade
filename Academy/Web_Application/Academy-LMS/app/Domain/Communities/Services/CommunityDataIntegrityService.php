<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\CommunityPostLike;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityDataIntegrityService
{
    public function runMaintenance(Community $community): array
    {
        return [
            'posts_reconciled' => $this->reconcilePostCounters($community),
            'members_reconciled' => $this->syncMemberPoints($community),
        ];
    }

    public function pruneOrphans(): array
    {
        $removedPosts = CommunityPost::whereDoesntHave('community')->forceDelete();
        $removedComments = CommunityPostComment::whereDoesntHave('post')->forceDelete();
        $removedLikes = CommunityPostLike::whereDoesntHave('post')->delete();
        $removedMemberLikes = DB::table('community_comment_likes')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('community_post_comments')
                    ->whereColumn('community_post_comments.id', 'community_comment_likes.comment_id');
            })
            ->delete();

        return [
            'posts_pruned' => $removedPosts,
            'comments_pruned' => $removedComments,
            'post_likes_pruned' => $removedLikes,
            'comment_likes_pruned' => $removedMemberLikes,
        ];
    }

    public function analyzeTables(): void
    {
        foreach (config('communities.maintenance.tables', []) as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                DB::statement("CHECK TABLE {$table}");
            } catch (\Throwable $exception) {
                Log::warning('Community maintenance analysis failed', [
                    'table' => $table,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function reconcilePostCounters(Community $community): int
    {
        $likes = CommunityPostLike::query()
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereHas('post', fn ($query) => $query->where('community_id', $community->id))
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $comments = CommunityPostComment::query()
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->where('community_id', $community->id)
            ->whereNull('deleted_at')
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $updated = 0;

        CommunityPost::where('community_id', $community->id)
            ->chunkById(config('communities.maintenance.chunk', 200), function ($posts) use (&$updated, $likes, $comments) {
                foreach ($posts as $post) {
                    $likeCount = (int) ($likes[$post->id] ?? 0);
                    $commentCount = (int) ($comments[$post->id] ?? 0);

                    if ((int) $post->like_count !== $likeCount || (int) $post->comment_count !== $commentCount) {
                        $post->update([
                            'like_count' => $likeCount,
                            'comment_count' => $commentCount,
                        ]);

                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function syncMemberPoints(Community $community): int
    {
        $balances = CommunityPointsLedger::query()
            ->select('member_id', DB::raw('SUM(points_delta) as total'))
            ->where('community_id', $community->id)
            ->groupBy('member_id')
            ->pluck('total', 'member_id');

        $updated = 0;

        CommunityMember::where('community_id', $community->id)
            ->chunkById(config('communities.maintenance.chunk', 200), function ($members) use (&$updated, $balances) {
                foreach ($members as $member) {
                    $balance = (int) ($balances[$member->id] ?? 0);
                    $normalizedLifetime = max($balance, 0);
                    $lifetime = max((int) $member->lifetime_points, $normalizedLifetime);

                    if ((int) $member->points !== $balance || (int) $member->lifetime_points !== $lifetime) {
                        $member->update([
                            'points' => $balance,
                            'lifetime_points' => $lifetime,
                        ]);

                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
