<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPaywallAccess;
use App\Domain\Communities\Models\CommunitySinglePurchase;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Domain\Search\Data\SearchVisibilityContext;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SearchVisibilityService
{
    public function __construct(
        private readonly ?int $tokenTtl = null
    ) {
    }

    public function forUser(?User $user): SearchVisibilityContext
    {
        $now = CarbonImmutable::now();
        $ttl = $this->tokenTtl ?? (int) config('search.visibility.ttl', 900);
        $expiresAt = $now->addSeconds(max($ttl, 60));

        if ($user === null) {
            return new SearchVisibilityContext(
                null,
                [],
                [],
                [],
                includePublic: true,
                includeCommunity: false,
                includePaid: false,
                issuedAt: $now,
                expiresAt: $expiresAt,
            );
        }

        $communityIds = $this->activeMembershipCommunityIds($user);
        [$unrestrictedPaidCommunities, $subscriptionTierIds] = $this->resolvePaidAccess($user, $communityIds, $now);

        return new SearchVisibilityContext(
            $user->getKey(),
            $communityIds,
            $unrestrictedPaidCommunities,
            $subscriptionTierIds,
            includePublic: true,
            includeCommunity: ! empty($communityIds),
            includePaid: ! empty($unrestrictedPaidCommunities) || ! empty($subscriptionTierIds),
            issuedAt: $now,
            expiresAt: $expiresAt,
        );
    }

    /**
     * @return array<int, int>
     */
    protected function activeMembershipCommunityIds(User $user): array
    {
        return CommunityMember::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->pluck('community_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $communityIds
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    protected function resolvePaidAccess(User $user, array $communityIds, CarbonImmutable $now): array
    {
        $unrestricted = collect();
        $tierIds = collect();

        $subscriptions = CommunitySubscription::query()
            ->select(['community_id', 'subscription_tier_id', 'status', 'ended_at'])
            ->where('user_id', $user->getKey())
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) use ($now) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', $now);
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $unrestricted->push((int) $subscription->community_id);

            if ($subscription->subscription_tier_id !== null) {
                $tierIds->push((int) $subscription->subscription_tier_id);
            }
        }

        $paywallAccess = CommunityPaywallAccess::query()
            ->select(['community_id', 'subscription_tier_id', 'access_starts_at', 'access_ends_at'])
            ->where('user_id', $user->getKey())
            ->where(function ($query) use ($now) {
                $query->whereNull('access_starts_at')
                    ->orWhere('access_starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('access_ends_at')
                    ->orWhere('access_ends_at', '>', $now);
            })
            ->get();

        foreach ($paywallAccess as $access) {
            $unrestricted->push((int) $access->community_id);

            if ($access->subscription_tier_id !== null) {
                $tierIds->push((int) $access->subscription_tier_id);
            }
        }

        $singlePurchases = CommunitySinglePurchase::query()
            ->select(['community_id', 'purchasable_type', 'purchasable_id'])
            ->where('user_id', $user->getKey())
            ->get();

        foreach ($singlePurchases as $purchase) {
            $unrestricted->push((int) $purchase->community_id);

            if ($purchase->purchasable_type === CommunitySubscriptionTier::class && $purchase->purchasable_id !== null) {
                $tierIds->push((int) $purchase->purchasable_id);
            }
        }

        $unrestricted = $this->uniqueIntegers($unrestricted);

        if (empty($unrestricted) && ! empty($communityIds)) {
            $unrestricted = $communityIds;
        }

        return [
            $unrestricted,
            $this->uniqueIntegers($tierIds),
        ];
    }

    /**
     * @param Collection<int, int>|array<int, int> $values
     * @return array<int, int>
     */
    protected function uniqueIntegers(Collection|array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}

