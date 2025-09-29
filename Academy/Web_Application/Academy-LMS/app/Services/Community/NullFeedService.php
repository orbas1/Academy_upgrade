<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullFeedService implements FeedService
{
    use NotImplemented;
    public function getCommunityFeed(\App\Models\Community\Community $community, ?\App\Models\Community\CommunityMember $member, string $filter, int $perPage = 20, ?string $cursor = null): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        $this->notImplemented();
    }

    public function getPinnedPosts(\App\Models\Community\Community $community): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function getMediaFeed(\App\Models\Community\Community $community, ?\App\Models\Community\CommunityMember $member, int $perPage = 20, ?string $cursor = null): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        $this->notImplemented();
    }

    public function getPostWithContext(\App\Models\Community\Community $community, \App\Models\Community\CommunityPost $post, ?\App\Models\Community\CommunityMember $member = null): array
    {
        $this->notImplemented();
    }
}
