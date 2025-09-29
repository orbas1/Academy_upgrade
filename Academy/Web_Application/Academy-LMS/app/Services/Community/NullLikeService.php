<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullLikeService implements LikeService
{
    use NotImplemented;
    public function likePost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $member): \App\Models\Community\CommunityPostLike
    {
        $this->notImplemented();
    }

    public function unlikePost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $member): void
    {
        $this->notImplemented();
    }

    public function likeComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $member): \App\Models\Community\CommunityCommentLike
    {
        $this->notImplemented();
    }

    public function unlikeComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $member): void
    {
        $this->notImplemented();
    }

    public function hasLikedPost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $member): bool
    {
        $this->notImplemented();
    }

    public function hasLikedComment(\App\Models\Community\CommunityPostComment $comment, \App\Models\Community\CommunityMember $member): bool
    {
        $this->notImplemented();
    }
}
