<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunitySubscriptionInterval: string
{
    case MONTH = 'month';
    case YEAR = 'year';

    public function label(): string
    {
        return match ($this) {
            self::MONTH => 'communities.subscriptions.interval.month',
            self::YEAR => 'communities.subscriptions.interval.year',
        };
    }
}
