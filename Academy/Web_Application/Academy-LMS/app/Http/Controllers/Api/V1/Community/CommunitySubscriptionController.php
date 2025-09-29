<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\ManageSubscriptionRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use App\Services\Community\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunitySubscriptionController extends CommunityApiController
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $subscriptions = $this->subscriptions
            ->listSubscriptions($community, $request->all())
            ->map(fn (CommunitySubscription $subscription) => $this->presentSubscription($subscription))
            ->values();

        return $this->ok([
            'community_id' => $community->getKey(),
            'subscriptions' => $subscriptions,
        ]);
    }

    public function store(ManageSubscriptionRequest $request, Community $community, CommunityMember $member, CommunitySubscriptionTier $tier): JsonResponse
    {
        $payload = $request->validated();

        $providerPayload = [
            'metadata' => $payload['metadata'] ?? [],
        ];

        if (!empty($payload['payment_intent'])) {
            $providerPayload['provider_payment_intent_id'] = $payload['payment_intent'];
        }

        $subscription = $this->subscriptions->startSubscription($member, $tier, $providerPayload);

        return $this->ok([
            'community_id' => $community->getKey(),
            'subscription' => $this->presentSubscription($subscription),
        ], 201);
    }

    public function destroy(Request $request, Community $community, CommunitySubscription $subscription): JsonResponse
    {
        $immediate = $request->boolean('immediate');
        $this->subscriptions->cancelSubscription($subscription, $immediate);

        return $this->respondNoContent();
    }

    private function presentSubscription(CommunitySubscription $subscription): array
    {
        $subscription->loadMissing(['user', 'tier']);

        return [
            'id' => $subscription->getKey(),
            'user_id' => $subscription->user_id,
            'subscription_tier_id' => $subscription->subscription_tier_id,
            'status' => $subscription->status,
            'renews_at' => optional($subscription->renews_at)?->toIso8601String(),
            'ended_at' => optional($subscription->ended_at)?->toIso8601String(),
            'canceled_at' => optional($subscription->canceled_at)?->toIso8601String(),
            'provider' => $subscription->provider,
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'metadata' => $subscription->metadata,
            'tier' => $subscription->tier?->only([
                'id', 'name', 'slug', 'currency', 'price_cents', 'billing_interval', 'trial_days', 'is_public',
            ]),
            'user' => $subscription->user?->only(['id', 'name', 'email']),
            'created_at' => optional($subscription->created_at)?->toIso8601String(),
            'updated_at' => optional($subscription->updated_at)?->toIso8601String(),
        ];
    }
}
