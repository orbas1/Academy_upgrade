<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityFollowableType: string
{
    case COMMUNITY = 'community';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::COMMUNITY => 'communities.follows.type.community',
            self::USER => 'communities.follows.type.user',
        };
    }
}
