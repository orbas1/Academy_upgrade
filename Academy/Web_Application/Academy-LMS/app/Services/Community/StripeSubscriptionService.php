<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunitySubscriptionService as DomainSubscriptionService;
use App\Enums\Community\CommunitySubscriptionStatus;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use Illuminate\Support\Collection;

class StripeSubscriptionService implements SubscriptionService
{
    public function __construct(private readonly DomainSubscriptionService $domain)
    {
    }

    public function startSubscription(
        CommunityMember $member,
        CommunitySubscriptionTier $tier,
        array $providerPayload = []
    ): CommunitySubscription {
        $member->loadMissing(['community', 'user']);

        $payload = array_merge(['provider' => 'stripe'], $providerPayload);

        $subscription = $this->domain->subscribe(
            $member->community,
            $member->user,
            $tier,
            $payload
        );

        return CommunitySubscription::query()->findOrFail($subscription->getKey());
    }

    public function cancelSubscription(CommunitySubscription $subscription, bool $immediate = false): void
    {
        $subscription->loadMissing(['community', 'user']);

        $canceled = $this->domain->cancel($subscription, $immediate ? now()->toImmutable() : null);
        $subscription->fill($canceled->getAttributes());
    }

    public function syncSubscriptionStatus(
        CommunitySubscription $subscription,
        CommunitySubscriptionStatus $status,
        array $providerPayload = []
    ): CommunitySubscription {
        $subscription->loadMissing(['community']);

        $payload = array_merge(
            $providerPayload,
            [
                'user_id' => $subscription->user_id,
                'subscription_tier_id' => $subscription->subscription_tier_id,
                'status' => $status->value,
                'provider_subscription_id' => $subscription->provider_subscription_id,
            ],
        );

        $synced = $this->domain->syncFromWebhook($subscription->community, $payload);

        return CommunitySubscription::query()->findOrFail($synced->getKey());
    }

    public function listSubscriptions(Community $community, array $filters = []): Collection
    {
        $query = CommunitySubscription::query()
            ->where('community_id', $community->getKey())
            ->with(['user', 'tier']);

        if (!empty($filters['status'])) {
            $status = (array) $filters['status'];
            $query->whereIn('status', $status);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['subscription_tier_id'])) {
            $query->where('subscription_tier_id', $filters['subscription_tier_id']);
        }

        $subscriptions = $query->orderByDesc('created_at')->get();

        return $subscriptions instanceof Collection ? $subscriptions : new Collection($subscriptions->all());
    }
}
