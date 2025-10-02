<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Awards and tracks community points with daily caps and events.
 */
interface PointsService
{
    /**
     * Award points for a named event and emit audit data.
     *
     * @param  array{metadata?:array}  $context
     * @return array{
     *     user_id:int,
     *     community_id:int|null,
     *     event:string,
     *     points:int,
     *     total:int,
     *     awarded_at:Carbon,
     *     capped:boolean
     * }
     */
    public function award(int $userId, string $event, int $points, array $context = []): array;

    /**
     * Return the remaining allowance for the provided user and event key.
     *
     * @return array{event:string,remaining:int,reset_at:Carbon}
     */
    public function remainingForToday(int $userId, string $event): array;
}
