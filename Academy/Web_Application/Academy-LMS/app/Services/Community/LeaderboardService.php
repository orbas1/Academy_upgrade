<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Models\Community\Community;
use App\Models\Community\CommunityLeaderboard;
use App\Models\Community\CommunityMember;
use Illuminate\Support\Collection;

/**
 * Contract coordinating leaderboard snapshots and queries.
 */
interface LeaderboardService
{
    public function snapshot(Community $community, CommunityLeaderboardPeriod $period): CommunityLeaderboard;

    public function getLeaderboard(Community $community, CommunityLeaderboardPeriod $period, int $limit = 25): Collection;

    public function getMemberStanding(CommunityMember $member, CommunityLeaderboardPeriod $period): ?array;
}
