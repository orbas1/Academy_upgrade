<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullCommentService implements CommentService
{
    use NotImplemented;
    public function createComment(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $author, array $payload): \App\Models\Community\CommunityPostComment
    {
        $this->notImplemented();
    }

    public function updateComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $actor, array $payload): \App\Models\Community\CommunityPostComment
    {
        $this->notImplemented();
    }

    public function deleteComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $actor): void
    {
        $this->notImplemented();
    }

    public function restoreComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $actor): \App\Models\Community\CommunityPostComment
    {
        $this->notImplemented();
    }

    public function fetchThread(\App\Models\Community\CommunityPost $post, ?\App\Models\Community\CommunityPostComment $parent = null, int $depth = 2): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }
}
