<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityJoinPolicy: string
{
    case OPEN = 'open';
    case REQUEST = 'request';
    case INVITE = 'invite';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'communities.join_policy.open',
            self::REQUEST => 'communities.join_policy.request',
            self::INVITE => 'communities.join_policy.invite',
        };
    }
}
