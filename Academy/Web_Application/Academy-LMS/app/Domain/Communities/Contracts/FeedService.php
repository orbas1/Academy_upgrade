<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only access layer for Orbas Learn community feeds with keyset pagination and visibility gates.
 */
interface FeedService
{
    /**
     * Fetch the latest posts ordered by creation time.
     *
     * @param  array{limit?:int,after?:string,before?:string,filters?:array}  $options
     * @return array{items:Collection<int, array>, next_cursor:?string, prev_cursor:?string, generated_at:Carbon}
     */
    public function listNew(int $communityId, array $options = []): array;

    /**
     * Fetch top posts ranked by engagement score.
     *
     * @param  array{limit?:int,after?:string,before?:string,filters?:array}  $options
     * @return array{items:Collection<int, array>, next_cursor:?string, prev_cursor:?string, generated_at:Carbon}
     */
    public function listTop(int $communityId, array $options = []): array;

    /**
     * Fetch posts that include media attachments.
     *
     * @param  array{limit?:int,after?:string,before?:string,filters?:array}  $options
     * @return array{items:Collection<int, array>, next_cursor:?string, prev_cursor:?string, generated_at:Carbon}
     */
    public function listMedia(int $communityId, array $options = []): array;

    /**
     * Fetch pinned posts surfaced to the top of the feed.
     *
     * @return array{items:Collection<int, array>, generated_at:Carbon}
     */
    public function listPins(int $communityId): array;
}
