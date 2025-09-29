<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityMemberStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case BANNED = 'banned';
    case LEFT = 'left';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'communities.members.status.active',
            self::PENDING => 'communities.members.status.pending',
            self::BANNED => 'communities.members.status.banned',
            self::LEFT => 'communities.members.status.left',
        };
    }
}
