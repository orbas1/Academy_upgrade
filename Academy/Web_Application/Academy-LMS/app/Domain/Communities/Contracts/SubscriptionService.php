<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Coordinates Stripe subscription lifecycle for Orbas Learn communities.
 */
interface SubscriptionService
{
    /**
     * Ensure a Stripe customer exists for the user and return identifiers.
     *
     * @return array{user_id:int,stripe_customer_id:string,created:boolean}
     */
    public function ensureCustomer(int $userId, array $attributes = []): array;

    /**
     * Attach the user to the requested subscription tier within the community.
     *
     * @param  array{price_id:string,trial_until?:Carbon|null}  $options
     * @return array{
     *     subscription_id:string,
     *     status:string,
     *     current_period_end:Carbon,
     *     latest_invoice_url:?string
     * }
     */
    public function subscribe(int $communityId, int $userId, array $options): array;

    /**
     * Cancel an active subscription at period end or immediately.
     */
    public function cancel(int $communityId, int $userId, bool $immediately = false): void;

    /**
     * Evaluate whether the user currently holds an entitlement to the paywalled resource.
     *
     * @param  array{grace_seconds?:int}  $options
     * @return array{entitled:bool,subscription_status:?string,valid_until:?Carbon}
     */
    public function checkEntitlement(int $communityId, int $userId, array $options = []): array;
}
