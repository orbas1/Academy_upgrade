<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunitySubscriptionStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case PAST_DUE = 'past_due';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'communities.subscriptions.status.active',
            self::CANCELLED => 'communities.subscriptions.status.cancelled',
            self::PAST_DUE => 'communities.subscriptions.status.past_due',
        };
    }
}
