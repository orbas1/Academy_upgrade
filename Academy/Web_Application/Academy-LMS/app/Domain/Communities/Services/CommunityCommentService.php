<?php

namespace App\Domain\Communities\Services;

use App\Domain\Analytics\Services\AnalyticsDispatcher;
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
    public function __construct(private readonly AnalyticsDispatcher $analytics)
    {
    }

    public function createComment(CommunityPost $post, User $author, array $payload): CommunityPostComment
    {
        return DB::transaction(function () use ($post, $author, $payload) {
            $community = $post->community ?? $post->community()->first();
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
                $freshComment = $comment->fresh('author');
                event(new CommentCreated(
                    $membership,
                    $post->fresh('author'),
                    $freshComment
                ));

                $this->analytics->record('comment_create', $author, [
                    'community_id' => $post->community_id,
                    'post_id' => $post->getKey(),
                    'comment_id' => $freshComment->getKey(),
                    'parent_id' => $freshComment->parent_id,
                ], $community);
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

        $updated = $comment->fresh('post');

        if ($updated) {
            $updated->loadMissing('post.community', 'author');
        }

        if ($updated?->post?->community && $updated->author) {
            $this->analytics->record('comment_update', $updated->author, [
                'community_id' => $updated->post->community_id,
                'post_id' => $updated->post_id,
                'comment_id' => $updated->getKey(),
            ], $updated->post->community);
        }

        return $comment;
    }

    public function deleteComment(CommunityPostComment $comment, ?User $actor = null): void
    {
        DB::transaction(function () use ($comment, $actor): void {
            $community = $comment->post?->community ?? $comment->post()->with('community')->first()?->community;
            $comment->delete();

            CommunityPost::query()
                ->whereKey($comment->post_id)
                ->decrement('comment_count');

            if ($comment->parent_id) {
                CommunityPostComment::query()
                    ->where('id', $comment->parent_id)
                    ->decrement('reply_count');
            }

            if ($actor) {
                $this->analytics->record('comment_delete', $actor, [
                    'community_id' => $comment->community_id,
                    'post_id' => $comment->post_id,
                    'comment_id' => $comment->getKey(),
                ], $community);
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

