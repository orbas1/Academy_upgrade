<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\CommunityCommentLike;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use App\Models\Community\CommunityPostLike;

/**
 * Contract for idempotent like/unlike operations.
 */
interface LikeService
{
    public function likePost(CommunityPost $post, CommunityMember $member): CommunityPostLike;

    public function unlikePost(CommunityPost $post, CommunityMember $member): void;

    public function likeComment(CommunityPostComment $comment, CommunityMember $member): CommunityCommentLike;

    public function unlikeComment(CommunityPostComment $comment, CommunityMember $member): void;

    public function hasLikedPost(CommunityPost $post, CommunityMember $member): bool;

    public function hasLikedComment(CommunityPostComment $comment, CommunityMember $member): bool;
}
