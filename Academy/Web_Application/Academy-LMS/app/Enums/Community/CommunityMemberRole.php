<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityMemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'communities.members.roles.owner',
            self::ADMIN => 'communities.members.roles.admin',
            self::MODERATOR => 'communities.members.roles.moderator',
            self::MEMBER => 'communities.members.roles.member',
        };
    }
}
