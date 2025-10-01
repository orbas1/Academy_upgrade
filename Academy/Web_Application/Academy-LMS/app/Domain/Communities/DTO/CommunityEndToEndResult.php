<?php

declare(strict_types=1);

namespace App\Domain\Communities\DTO;

class CommunityEndToEndResult
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $community
     * @param  array<string, mixed>  $users
     * @param  array<string, mixed>  $subscription
     * @param  array<string, mixed>  $post
     * @param  array<string, mixed>  $comment
     * @param  array<string, mixed>  $points
     * @param  array<int, array<string, mixed>>  $leaderboard
     * @param  array<int, array<string, mixed>>  $notifications
     */
    public function __construct(
        public readonly array $meta,
        public readonly array $community,
        public readonly array $users,
        public readonly array $subscription,
        public readonly array $post,
        public readonly array $comment,
        public readonly array $points,
        public readonly array $leaderboard,
        public readonly array $notifications,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => 'ok',
            'meta' => $this->meta,
            'community' => $this->community,
            'users' => $this->users,
            'subscription' => $this->subscription,
            'post' => $this->post,
            'comment' => $this->comment,
            'points' => $this->points,
            'leaderboard' => $this->leaderboard,
            'notifications' => $this->notifications,
        ];
    }
}
