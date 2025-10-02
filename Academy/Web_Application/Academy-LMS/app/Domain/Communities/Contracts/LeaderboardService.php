<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Provides leaderboard snapshots and rankings for Orbas Learn communities.
 */
interface LeaderboardService
{
    /**
     * @param  array{span:string,generated_at?:Carbon|null}  $options
     * @return array{span:string,generated_at:Carbon,entries:Collection<int, array>}
     */
    public function snapshot(int $communityId, array $options = []): array;

    /**
     * Read the current leaderboard standings.
     *
     * @param  array{span?:string,limit?:int,after_rank?:int}  $options
     * @return array{span:string,entries:Collection<int, array>,next_rank:?int}
     */
    public function current(int $communityId, array $options = []): array;

    /**
     * Resolve a specific member's rank within the given leaderboard span.
     *
     * @return array{user_id:int,span:string,rank:int,points:int}
     */
    public function rankFor(int $communityId, int $userId, string $span): array;
}
