<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPaywallAccess;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySinglePurchase;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;

class CommunityPaywallService
{
    public function checkAccess(Community $community, User $user, ?CommunityPost $post = null): bool
    {
        $membership = CommunityMember::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->exists();

        if ($post === null || $post->visibility !== 'paid') {
            return $membership;
        }

        return $membership && $this->hasEntitlement($community, $user, $post->paywall_tier_id);
    }

    public function hasEntitlement(Community $community, User $user, ?int $tierId): bool
    {
        if ($tierId === null) {
            return true;
        }

        $now = CarbonImmutable::now();

        $subscription = CommunitySubscription::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->where('subscription_tier_id', $tierId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) use ($now) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $now);
            })
            ->exists();

        if ($subscription) {
            return true;
        }

        $access = CommunityPaywallAccess::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->where(function ($query) use ($tierId) {
                $query->whereNull('subscription_tier_id')
                    ->orWhere('subscription_tier_id', $tierId);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('access_ends_at')
                    ->orWhere('access_ends_at', '>', $now);
            })
            ->exists();

        if ($access) {
            return true;
        }

        return CommunitySinglePurchase::query()
            ->where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->where('purchasable_type', CommunitySubscriptionTier::class)
            ->where('purchasable_id', $tierId)
            ->exists();
    }

    public function grantTemporaryAccess(Community $community, User $user, ?int $tierId, CarbonImmutable $expiresAt, ?int $grantedBy = null): CommunityPaywallAccess
    {
        return CommunityPaywallAccess::updateOrCreate(
            [
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'subscription_tier_id' => $tierId,
            ],
            [
                'access_starts_at' => CarbonImmutable::now(),
                'access_ends_at' => $expiresAt,
                'granted_by' => $grantedBy,
            ]
        );
    }
}

