<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullLeaderboardService implements LeaderboardService
{
    use NotImplemented;
    public function snapshot(\App\Models\Community\Community $community, \App\Enums\Community\CommunityLeaderboardPeriod $period): \App\Models\Community\CommunityLeaderboard
    {
        $this->notImplemented();
    }

    public function getLeaderboard(\App\Models\Community\Community $community, \App\Enums\Community\CommunityLeaderboardPeriod $period, int $limit = 25): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function getMemberStanding(\App\Models\Community\CommunityMember $member, \App\Enums\Community\CommunityLeaderboardPeriod $period): ?array
    {
        $this->notImplemented();
    }
}
