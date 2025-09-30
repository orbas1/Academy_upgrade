<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityReactionService;
use App\Models\Community\CommunityCommentLike;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use App\Models\Community\CommunityPostLike;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EloquentLikeService implements LikeService
{
    public function __construct(private readonly CommunityReactionService $reactions)
    {
    }

    public function likePost(CommunityPost $post, CommunityMember $member): CommunityPostLike
    {
        $this->assertSameCommunity($post->community_id, $member->community_id);

        $existing = CommunityPostLike::query()
            ->where('post_id', $post->getKey())
            ->where('user_id', $member->user_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->reactions->togglePostReaction($post, $member->user, 'like');
    }

    public function unlikePost(CommunityPost $post, CommunityMember $member): void
    {
        $this->assertSameCommunity($post->community_id, $member->community_id);

        $like = CommunityPostLike::query()
            ->where('post_id', $post->getKey())
            ->where('user_id', $member->user_id)
            ->first();

        if (! $like) {
            return;
        }

        DB::transaction(function () use ($like, $post): void {
            $like->delete();
            $post->decrement('like_count');
        });
    }

    public function likeComment(CommunityPostComment $comment, CommunityMember $member): CommunityCommentLike
    {
        $this->assertSameCommunity($comment->community_id, $member->community_id);

        $existing = CommunityCommentLike::query()
            ->where('comment_id', $comment->getKey())
            ->where('user_id', $member->user_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->reactions->toggleCommentReaction($comment, $member->user, 'like');
    }

    public function unlikeComment(CommunityPostComment $comment, CommunityMember $member): void
    {
        $this->assertSameCommunity($comment->community_id, $member->community_id);

        $like = CommunityCommentLike::query()
            ->where('comment_id', $comment->getKey())
            ->where('user_id', $member->user_id)
            ->first();

        if (! $like) {
            return;
        }

        DB::transaction(function () use ($like, $comment): void {
            $like->delete();
            $comment->decrement('like_count');
        });
    }

    public function hasLikedPost(CommunityPost $post, CommunityMember $member): bool
    {
        $this->assertSameCommunity($post->community_id, $member->community_id);

        return CommunityPostLike::query()
            ->where('post_id', $post->getKey())
            ->where('user_id', $member->user_id)
            ->exists();
    }

    public function hasLikedComment(CommunityPostComment $comment, CommunityMember $member): bool
    {
        $this->assertSameCommunity($comment->community_id, $member->community_id);

        return CommunityCommentLike::query()
            ->where('comment_id', $comment->getKey())
            ->where('user_id', $member->user_id)
            ->exists();
    }

    private function assertSameCommunity(int $resourceCommunityId, int $memberCommunityId): void
    {
        if ($resourceCommunityId !== $memberCommunityId) {
            throw new InvalidArgumentException('Member must belong to the same community.');
        }
    }
}
