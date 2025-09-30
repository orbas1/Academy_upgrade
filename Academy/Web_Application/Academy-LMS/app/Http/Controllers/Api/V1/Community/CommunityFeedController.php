<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Services\Community\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityFeedController extends CommunityApiController
{
    public function __construct(private readonly FeedService $feed)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $filter = (string) $request->query('filter', 'new');
        $perPage = (int) min(max($request->integer('per_page', 20), 1), 100);
        $cursor = $request->query('cursor');
        $member = $this->resolveMember($request, $community);

        $paginator = $this->feed->getCommunityFeed($community, $member, $filter, $perPage, $cursor);

        return $this->respondWithPagination($paginator, [
            'community_id' => $community->getKey(),
            'filter' => $filter,
        ]);
    }

    public function pinned(Community $community): JsonResponse
    {
        $pinned = $this->feed->getPinnedPosts($community);

        return $this->ok([
            'community_id' => $community->getKey(),
            'pinned' => $pinned,
        ]);
    }

    private function resolveMember(Request $request, Community $community): ?CommunityMember
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return $community->members()
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->first();
    }
}
