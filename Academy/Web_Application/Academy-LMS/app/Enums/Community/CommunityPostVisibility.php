<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityPostVisibility: string
{
    case COMMUNITY = 'community';
    case PUBLIC = 'public';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::COMMUNITY => 'communities.posts.visibility.community',
            self::PUBLIC => 'communities.posts.visibility.public',
            self::PAID => 'communities.posts.visibility.paid',
        };
    }
}
