<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Ensures idempotent like/unlike flows for posts and comments.
 */
interface LikeService
{
    /**
     * @return array{liked:bool,like_count:int,updated_at:Carbon}
     */
    public function like(string $likeableType, int $likeableId, int $userId): array;

    /**
     * @return array{liked:bool,like_count:int,updated_at:Carbon}
     */
    public function unlike(string $likeableType, int $likeableId, int $userId): array;
}
