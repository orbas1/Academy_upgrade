<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\CommunityCommentLike;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\CommunityPostLike;
use App\Events\Community\PostLiked;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CommunityReactionService
{
    public function togglePostReaction(CommunityPost $post, User $user, string $reaction = 'like'): CommunityPostLike
    {
        return DB::transaction(function () use ($post, $user, $reaction) {
            $existing = CommunityPostLike::query()
                ->where('post_id', $post->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if ($existing && $existing->reaction === $reaction) {
                $existing->delete();
                $post->decrement('like_count');

                return $existing;
            }

            $reactionCreated = false;

            if ($existing) {
                $existing->reaction = $reaction;
                $existing->reacted_at = CarbonImmutable::now();
                $existing->save();
            } else {
                $existing = CommunityPostLike::create([
                    'post_id' => $post->getKey(),
                    'user_id' => $user->getKey(),
                    'reaction' => $reaction,
                    'reacted_at' => CarbonImmutable::now(),
                ]);
                $post->increment('like_count');
                $reactionCreated = true;
            }

            if ($existing && ($existing->wasRecentlyCreated || $reactionCreated)) {
                $membership = CommunityMember::query()
                    ->where('community_id', $post->community_id)
                    ->where('user_id', $user->getKey())
                    ->first();

                if ($membership) {
                    event(new PostLiked($membership, $post->fresh('author'), $existing));
                }
            }

            return $existing;
        });
    }

    public function toggleCommentReaction(CommunityPostComment $comment, User $user, string $reaction = 'like'): CommunityCommentLike
    {
        return DB::transaction(function () use ($comment, $user, $reaction) {
            $existing = CommunityCommentLike::query()
                ->where('comment_id', $comment->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if ($existing && $existing->reaction === $reaction) {
                $existing->delete();
                $comment->decrement('like_count');

                return $existing;
            }

            if ($existing) {
                $existing->reaction = $reaction;
                $existing->reacted_at = CarbonImmutable::now();
                $existing->save();
            } else {
                $existing = CommunityCommentLike::create([
                    'comment_id' => $comment->getKey(),
                    'user_id' => $user->getKey(),
                    'reaction' => $reaction,
                    'reacted_at' => CarbonImmutable::now(),
                ]);
                $comment->increment('like_count');
            }

            return $existing;
        });
    }
}

