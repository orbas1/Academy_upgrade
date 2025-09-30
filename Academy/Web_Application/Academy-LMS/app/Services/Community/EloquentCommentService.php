<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityCommentService;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EloquentCommentService implements CommentService
{
    public function __construct(private readonly CommunityCommentService $comments)
    {
    }

    public function createComment(CommunityPost $post, CommunityMember $author, array $payload): CommunityPostComment
    {
        if ((int) $author->community_id !== (int) $post->community_id) {
            throw new InvalidArgumentException('Author must belong to the same community as the post.');
        }

        $user = $author->user;
        $comment = $this->comments->createComment($post, $user, [
            'body_md' => $payload['body_md'],
            'body_html' => $payload['body_html'] ?? null,
            'mentions' => $payload['mentions'] ?? [],
            'parent_id' => $payload['parent_id'] ?? null,
            'is_locked' => (bool) ($payload['is_locked'] ?? false),
            'is_pinned' => (bool) ($payload['is_pinned'] ?? false),
        ]);

        return $comment->fresh(['author']);
    }

    public function updateComment(CommunityPostComment $comment, CommunityMember $actor, array $payload): CommunityPostComment
    {
        $this->assertSameCommunity($comment, $actor);

        $comment = $this->comments->updateComment($comment, [
            'body_md' => $payload['body_md'] ?? $comment->body_md,
            'body_html' => $payload['body_html'] ?? $comment->body_html,
            'mentions' => $payload['mentions'] ?? $comment->mentions,
            'is_locked' => $payload['is_locked'] ?? $comment->is_locked,
            'is_pinned' => $payload['is_pinned'] ?? $comment->is_pinned,
        ]);

        return $comment->fresh(['author']);
    }

    public function deleteComment(CommunityPostComment $comment, CommunityMember $actor): void
    {
        $this->assertSameCommunity($comment, $actor);
        $this->comments->deleteComment($comment, $actor->user);
    }

    public function restoreComment(CommunityPostComment $comment, CommunityMember $actor): CommunityPostComment
    {
        $this->assertSameCommunity($comment, $actor);

        if (! $comment->trashed()) {
            return $comment->fresh();
        }

        $comment->restore();
        $comment->post()->increment('comment_count');

        if ($comment->parent_id) {
            CommunityPostComment::query()
                ->where('id', $comment->parent_id)
                ->increment('reply_count');
        }

        return $comment->fresh(['author']);
    }

    public function fetchThread(CommunityPost $post, ?CommunityPostComment $parent = null, int $depth = 2): Collection
    {
        $query = CommunityPostComment::query()
            ->with(['author:id,name', 'replies'])
            ->where('post_id', $post->getKey())
            ->whereNull('deleted_at')
            ->orderBy('created_at');

        if ($parent) {
            $query->where('parent_id', $parent->getKey());
        } else {
            $query->whereNull('parent_id');
        }

        $comments = $query->get();

        return $comments->map(fn (CommunityPostComment $comment) => $this->transformComment($comment, $depth - 1));
    }

    private function transformComment(CommunityPostComment $comment, int $remainingDepth): array
    {
        $payload = [
            'id' => (int) $comment->getKey(),
            'author_id' => (int) $comment->author_id,
            'author_name' => $comment->author?->name,
            'body_md' => $comment->body_md,
            'body_html' => $comment->body_html,
            'mentions' => $comment->mentions,
            'is_pinned' => (bool) $comment->is_pinned,
            'is_locked' => (bool) $comment->is_locked,
            'like_count' => (int) $comment->like_count,
            'reply_count' => (int) $comment->reply_count,
            'created_at' => optional($comment->created_at)->toIso8601String(),
            'updated_at' => optional($comment->updated_at)->toIso8601String(),
        ];

        if ($remainingDepth > 0) {
            $payload['replies'] = $comment->replies
                ->whereNull('deleted_at')
                ->map(fn (CommunityPostComment $reply) => $this->transformComment($reply, $remainingDepth - 1))
                ->values()
                ->all();
        } else {
            $payload['replies'] = [];
        }

        return $payload;
    }

    private function assertSameCommunity(CommunityPostComment $comment, CommunityMember $member): void
    {
        if ((int) $comment->community_id !== (int) $member->community_id) {
            throw new InvalidArgumentException('Member must belong to the same community as the comment.');
        }
    }
}
