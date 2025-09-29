<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Models\Community\Community;
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
        return $this->ok([
            'community_id' => $community->getKey(),
            'filter' => $request->get('filter', 'new'),
        ]);
    }

    public function pinned(Community $community): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'pinned' => [],
        ]);
    }
}
