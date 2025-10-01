<?php

declare(strict_types=1);

namespace App\Domain\Communities\DTO;

use JsonSerializable;

final class CommunityLoadTestSummary implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        public readonly int $communities,
        public readonly int $members,
        public readonly int $posts,
        public readonly int $comments,
        public readonly int $reactions,
        public readonly int $pointsEvents,
        public readonly int $profileActivities,
        public readonly array $credentials,
    ) {
    }

    /**
     * @return array<string, int|array<string, mixed>>
     */
    public function toArray(): array
    {
        return [
            'communities' => $this->communities,
            'members' => $this->members,
            'posts' => $this->posts,
            'comments' => $this->comments,
            'reactions' => $this->reactions,
            'points_events' => $this->pointsEvents,
            'profile_activities' => $this->profileActivities,
            'credentials' => $this->credentials,
        ];
    }

    /**
     * @return array<string, int|array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
