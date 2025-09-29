<?php

namespace App\Domain\Communities\Observers;

use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\CommunityPostLike;
use App\Domain\Communities\Services\CommunityContentSanitizer;

class CommunityPostObserver
{
    public function __construct(private readonly CommunityContentSanitizer $sanitizer)
    {
    }

    public function saving(CommunityPost $post): void
    {
        $post->body_md = $this->sanitizer->sanitizeMarkdown($post->body_md);
        $post->body_html = $this->sanitizer->sanitizeHtml($post->body_html);
    }

    public function deleting(CommunityPost $post): void
    {
        if ($post->isForceDeleting()) {
            CommunityPostComment::withTrashed()->where('post_id', $post->id)->forceDelete();
            CommunityPostLike::where('post_id', $post->id)->delete();

            return;
        }

        CommunityPostComment::where('post_id', $post->id)
            ->chunkById(config('communities.maintenance.chunk', 100), function ($comments) {
                $comments->each->delete();
            });

        CommunityPostLike::where('post_id', $post->id)->delete();
    }

    public function restored(CommunityPost $post): void
    {
        CommunityPostComment::withTrashed()
            ->where('post_id', $post->id)
            ->chunkById(config('communities.maintenance.chunk', 100), function ($comments) {
                $comments->each->restore();
            });
    }
}
