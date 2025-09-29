<?php

namespace App\Domain\Communities\Observers;

use App\Domain\Communities\Models\CommunityCommentLike;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Services\CommunityContentSanitizer;

class CommunityPostCommentObserver
{
    public function __construct(private readonly CommunityContentSanitizer $sanitizer)
    {
    }

    public function saving(CommunityPostComment $comment): void
    {
        $comment->body_md = $this->sanitizer->sanitizeMarkdown($comment->body_md);
        $comment->body_html = $this->sanitizer->sanitizeHtml($comment->body_html);
    }

    public function deleting(CommunityPostComment $comment): void
    {
        if ($comment->isForceDeleting()) {
            CommunityCommentLike::where('comment_id', $comment->id)->delete();

            return;
        }

        CommunityCommentLike::where('comment_id', $comment->id)->delete();

        $comment->replies()
            ->withTrashed()
            ->chunkById(config('communities.maintenance.chunk', 100), function ($replies) use ($comment) {
                foreach ($replies as $reply) {
                    if ($comment->isForceDeleting()) {
                        $reply->forceDelete();
                    } else {
                        $reply->delete();
                    }
                }
            });
    }

    public function restored(CommunityPostComment $comment): void
    {
        $comment->replies()
            ->withTrashed()
            ->chunkById(config('communities.maintenance.chunk', 100), function ($replies) {
                $replies->each->restore();
            });
    }
}
