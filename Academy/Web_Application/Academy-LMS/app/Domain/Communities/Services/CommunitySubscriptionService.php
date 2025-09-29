<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPaywallAccess;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Events\Community\PaymentSucceeded;
use App\Events\Community\SubscriptionStarted;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CommunitySubscriptionService
{
    public function subscribe(Community $community, User $user, CommunitySubscriptionTier $tier, array $payload = []): CommunitySubscription
    {
        return DB::transaction(function () use ($community, $user, $tier, $payload) {
            $subscription = CommunitySubscription::updateOrCreate(
                [
                    'community_id' => $community->getKey(),
                    'user_id' => $user->getKey(),
                    'subscription_tier_id' => $tier->getKey(),
                ],
                [
                    'provider' => $payload['provider'] ?? 'stripe',
                    'provider_subscription_id' => $payload['provider_subscription_id'] ?? null,
                    'status' => $payload['status'] ?? 'active',
                    'renews_at' => isset($payload['renews_at']) ? CarbonImmutable::parse($payload['renews_at']) : null,
                    'metadata' => $payload['metadata'] ?? [],
                ]
            );

            $membership = $this->ensureMembership($community, $user);

            $this->grantAccess($community, $user, $tier, $payload['access_expires_at'] ?? null, $payload['granted_by'] ?? null);

            $subscription->refresh();
            $membership->loadMissing(['user', 'community']);

            if (in_array($subscription->status, ['active', 'trialing'], true)) {
                event(new SubscriptionStarted($membership, $subscription));
                event(new PaymentSucceeded($membership, $subscription));
            }

            return $subscription;
        });
    }

    public function cancel(CommunitySubscription $subscription, ?CarbonImmutable $canceledAt = null): CommunitySubscription
    {
        $canceledAt = $canceledAt ?? CarbonImmutable::now();

        $subscription->status = 'canceled';
        $subscription->canceled_at = $canceledAt;
        $subscription->ended_at = $canceledAt;
        $subscription->save();

        CommunityPaywallAccess::query()
            ->where('community_id', $subscription->community_id)
            ->where('user_id', $subscription->user_id)
            ->where('subscription_tier_id', $subscription->subscription_tier_id)
            ->update(['access_ends_at' => $canceledAt]);

        return $subscription;
    }

    public function syncFromWebhook(Community $community, array $payload): CommunitySubscription
    {
        $user = User::query()->findOrFail($payload['user_id']);
        $tier = CommunitySubscriptionTier::query()->findOrFail($payload['subscription_tier_id']);

        $subscription = CommunitySubscription::updateOrCreate(
            [
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'subscription_tier_id' => $tier->getKey(),
            ],
            [
                'provider' => $payload['provider'] ?? 'stripe',
                'provider_subscription_id' => $payload['provider_subscription_id'] ?? null,
                'status' => $payload['status'] ?? 'active',
                'renews_at' => isset($payload['renews_at']) ? CarbonImmutable::parse($payload['renews_at']) : null,
                'ended_at' => isset($payload['ended_at']) ? CarbonImmutable::parse($payload['ended_at']) : null,
                'metadata' => $payload['metadata'] ?? [],
            ]
        );

        $membership = $this->ensureMembership($community, $user);

        if (($payload['status'] ?? 'active') !== 'active') {
            $this->cancel($subscription, isset($payload['ended_at']) ? CarbonImmutable::parse($payload['ended_at']) : null);
        } else {
            $this->grantAccess($community, $user, $tier, $payload['access_expires_at'] ?? null, $payload['granted_by'] ?? null);
            $subscription->refresh();
            $membership->loadMissing(['user', 'community']);

            event(new SubscriptionStarted($membership, $subscription));

            if (($payload['paid'] ?? true) === true) {
                event(new PaymentSucceeded($membership, $subscription));
            }
        }

        return $subscription;
    }

    protected function grantAccess(Community $community, User $user, CommunitySubscriptionTier $tier, ?string $expiresAt, ?int $grantedBy): void
    {
        CommunityPaywallAccess::updateOrCreate(
            [
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'subscription_tier_id' => $tier->getKey(),
            ],
            [
                'access_starts_at' => CarbonImmutable::now(),
                'access_ends_at' => $expiresAt ? CarbonImmutable::parse($expiresAt) : null,
                'granted_by' => $grantedBy,
            ]
        );
    }

    protected function ensureMembership(Community $community, User $user): CommunityMember
    {
        $membership = CommunityMember::firstOrCreate(
            [
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
            ],
            [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => CarbonImmutable::now(),
                'last_seen_at' => CarbonImmutable::now(),
                'is_online' => false,
            ]
        );

        if ($membership->status !== 'active') {
            $membership->forceFill(['status' => 'active'])->save();
        }

        return $membership;
    }
}

