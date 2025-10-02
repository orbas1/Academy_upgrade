<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Centralizes Orbas Learn paywall checks and single-purchase grants.
 */
interface PaywallService
{
    /**
     * Determine whether the actor may view the provided post.
     *
     * @param  array{skip_cache?:bool}  $options
     * @return array{allowed:bool,reason:?string,expires_at:?Carbon}
     */
    public function checkPostVisibility(int $postId, int $userId, array $options = []): array;

    /**
     * Grant a one-time purchase access to the resource.
     *
     * @param  array{price_id:string,expires_at?:Carbon|null,metadata?:array}  $payload
     * @return array{grant_id:int,post_id:int,user_id:int,expires_at:?Carbon}
     */
    public function grantSinglePurchase(int $postId, int $userId, array $payload): array;

    /**
     * Process incoming webhook data from Stripe or other billing providers.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void;
}
