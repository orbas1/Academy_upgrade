<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Collection;

/**
 * Contract for retrieving community feed data with pagination strategies.
 */
interface FeedService
{
    public function getCommunityFeed(Community $community, ?CommunityMember $member, string $filter, int $perPage = 20, ?string $cursor = null): CursorPaginator;

    public function getPinnedPosts(Community $community): Collection;

    public function getMediaFeed(Community $community, ?CommunityMember $member, int $perPage = 20, ?string $cursor = null): CursorPaginator;

    public function getPostWithContext(Community $community, CommunityPost $post, ?CommunityMember $member = null): array;
}
