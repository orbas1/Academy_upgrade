<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Models\Community\Community;
use App\Services\Community\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityLeaderboardController extends CommunityApiController
{
    public function __construct(private readonly LeaderboardService $leaderboard)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $period = CommunityLeaderboardPeriod::tryFrom($request->get('period', CommunityLeaderboardPeriod::DAILY->value)) ?? CommunityLeaderboardPeriod::DAILY;

        return $this->ok([
            'community_id' => $community->getKey(),
            'period' => $period->value,
        ]);
    }
}
