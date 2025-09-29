<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityPostType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case LINK = 'link';
    case POLL = 'poll';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'communities.posts.types.text',
            self::IMAGE => 'communities.posts.types.image',
            self::VIDEO => 'communities.posts.types.video',
            self::LINK => 'communities.posts.types.link',
            self::POLL => 'communities.posts.types.poll',
        };
    }
}
