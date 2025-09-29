<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityLeaderboardPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case ALLTIME = 'alltime';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'communities.leaderboards.periods.daily',
            self::WEEKLY => 'communities.leaderboards.periods.weekly',
            self::MONTHLY => 'communities.leaderboards.periods.monthly',
            self::ALLTIME => 'communities.leaderboards.periods.alltime',
        };
    }
}
