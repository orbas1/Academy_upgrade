<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Events\Community\CommentCreated;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityCommentService
{
    public function createComment(CommunityPost $post, User $author, array $payload): CommunityPostComment
    {
        return DB::transaction(function () use ($post, $author, $payload) {
            $comment = new CommunityPostComment();
            $comment->community_id = $post->community_id;
            $comment->post()->associate($post);
            $comment->author()->associate($author);
            $comment->body_md = $payload['body_md'];
            $comment->body_html = $payload['body_html'] ?? null;
            $comment->mentions = $payload['mentions'] ?? [];
            $comment->is_locked = (bool)($payload['is_locked'] ?? false);
            $comment->is_pinned = (bool)($payload['is_pinned'] ?? false);
            $comment->parent_id = $payload['parent_id'] ?? null;
            $comment->save();

            $post->increment('comment_count');

            if ($comment->parent_id) {
                CommunityPostComment::query()
                    ->where('id', $comment->parent_id)
                    ->increment('reply_count');
            }

            $membership = CommunityMember::query()
                ->where('community_id', $post->community_id)
                ->where('user_id', $author->getKey())
                ->first();

            if ($membership) {
                event(new CommentCreated(
                    $membership,
                    $post->fresh('author'),
                    $comment->fresh('author')
                ));
            }

            return $comment;
        });
    }

    public function updateComment(CommunityPostComment $comment, array $payload): CommunityPostComment
    {
        $comment->fill([
            'body_md' => $payload['body_md'] ?? $comment->body_md,
            'body_html' => $payload['body_html'] ?? $comment->body_html,
            'mentions' => $payload['mentions'] ?? $comment->mentions,
            'is_locked' => $payload['is_locked'] ?? $comment->is_locked,
            'is_pinned' => $payload['is_pinned'] ?? $comment->is_pinned,
        ]);
        $comment->save();

        return $comment;
    }

    public function deleteComment(CommunityPostComment $comment, ?User $actor = null): void
    {
        DB::transaction(function () use ($comment, $actor): void {
            $comment->delete();

            CommunityPost::query()
                ->whereKey($comment->post_id)
                ->decrement('comment_count');

            if ($comment->parent_id) {
                CommunityPostComment::query()
                    ->where('id', $comment->parent_id)
                    ->decrement('reply_count');
            }
        });
    }

    public function moderateComment(CommunityPostComment $comment, string $reason, ?CarbonImmutable $lockedUntil = null): CommunityPostComment
    {
        $comment->is_locked = true;
        $comment->save();

        Log::notice('community.comments.locked', [
            'comment_id' => $comment->getKey(),
            'community_id' => $comment->community_id,
            'post_id' => $comment->post_id,
            'reason' => $reason,
            'locked_until' => $lockedUntil?->toIso8601String(),
        ]);

        return $comment;
    }
}

