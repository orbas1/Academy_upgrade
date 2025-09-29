<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use Illuminate\Support\Collection;

/**
 * Contract for handling threaded comment operations.
 */
interface CommentService
{
    public function createComment(CommunityPost $post, CommunityMember $author, array $payload): CommunityPostComment;

    public function updateComment(CommunityPostComment $comment, CommunityMember $actor, array $payload): CommunityPostComment;

    public function deleteComment(CommunityPostComment $comment, CommunityMember $actor): void;

    public function restoreComment(CommunityPostComment $comment, CommunityMember $actor): CommunityPostComment;

    public function fetchThread(CommunityPost $post, ?CommunityPostComment $parent = null, int $depth = 2): Collection;
}
