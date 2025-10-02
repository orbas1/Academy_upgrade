<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Handles hierarchical comment threads for Orbas Learn community posts.
 */
interface CommentService
{
    /**
     * @param  array{
     *     author_id:int,
     *     body:string,
     *     parent_id?:int|null,
     *     visibility?:string
     * }  $payload
     * @return array{comment_id:int,post_id:int,parent_id:int|null,created_at:Carbon,visibility:string,is_soft_deleted:bool}
     */
    public function create(int $postId, array $payload): array;

    /**
     * Update an existing comment.
     *
     * @param  array{body?:string,visibility?:string}  $payload
     * @return array{comment_id:int,post_id:int,updated_at:Carbon,visibility:string}
     */
    public function update(int $commentId, array $payload): array;

    /**
     * Soft delete a comment with moderation rules.
     *
     * @param  array{reason?:string,actor_id?:int}  $context
     */
    public function softDelete(int $commentId, array $context = []): void;

    /**
     * Restore a previously soft deleted comment.
     */
    public function restore(int $commentId, int $actorId): void;

    /**
     * Return a threaded comment tree with pagination cursors.
     *
     * @param  array{limit?:int,after?:string,before?:string,depth?:int}  $options
     * @return array{items:array<int, array>, next_cursor:?string, prev_cursor:?string}
     */
    public function listForPost(int $postId, array $options = []): array;
}
