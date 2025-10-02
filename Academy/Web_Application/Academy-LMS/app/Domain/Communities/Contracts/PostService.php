<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Manages Orbas Learn community posts and associated counters.
 */
interface PostService
{
    /**
     * @param  array{
     *     author_id:int,
     *     body:string,
     *     visibility:string,
     *     scheduled_for?:Carbon|null,
     *     media?:array<int, array>,
     *     paywall?:array
     * }  $payload
     * @return array{post_id:int,community_id:int,created_at:Carbon,visibility:string,is_pinned:bool,is_locked:bool}
     */
    public function create(int $communityId, array $payload): array;

    /**
     * @param  array{
     *     body?:string,
     *     visibility?:string,
     *     media?:array<int, array>,
     *     paywall?:array,
     *     scheduled_for?:Carbon|null
     * }  $payload
     * @return array{post_id:int,community_id:int,updated_at:Carbon,visibility:string,is_pinned:bool,is_locked:bool}
     */
    public function update(int $postId, array $payload): array;

    /**
     * Delete (soft/hard) the provided post.
     */
    public function delete(int $postId, bool $force = false): void;

    /**
     * Pin or unpin a post inside the community feed.
     */
    public function togglePin(int $postId, bool $pinned, int $actorId): void;

    /**
     * Lock or unlock a post to prevent further replies.
     */
    public function toggleLock(int $postId, bool $locked, int $actorId): void;

    /**
     * Increment counters without triggering double counting.
     *
     * @param  array{views?:int,comments?:int,reactions?:int,shares?:int}  $increments
     */
    public function incrementCounters(int $postId, array $increments): void;
}
