<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case UNLISTED = 'unlisted';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'communities.visibility.public',
            self::PRIVATE => 'communities.visibility.private',
            self::UNLISTED => 'communities.visibility.unlisted',
        };
    }
}
