<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityPaywallService as DomainPaywallService;
use App\Enums\Community\CommunityPaywallAccessGrantedBy;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPaywallAccess;
use App\Models\Community\CommunityPaywallAccess as CommunityPaywallAccessModel;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunitySinglePurchase;
use App\Models\Community\CommunitySinglePurchase as CommunitySinglePurchaseModel;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscription as CommunitySubscriptionModel;
use App\Models\Community\CommunitySubscriptionTier;
use App\Models\Community\CommunitySubscriptionTier as CommunitySubscriptionTierModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EloquentPaywallService implements PaywallService
{
    public function __construct(private readonly DomainPaywallService $paywall)
    {
    }

    public function canAccessPost(CommunityPost $post, ?CommunityMember $member): bool
    {
        if ($member === null) {
            return false;
        }

        $member->loadMissing(['community', 'user']);
        if ($member->status !== 'active' || $member->community_id !== $post->community_id || $member->user === null) {
            return false;
        }

        if ($post->visibility !== 'paid') {
            return true;
        }

        $post->loadMissing('community');

        return $this->paywall->hasEntitlement($post->community, $member->user, $post->paywall_tier_id);
    }

    public function grantSinglePurchase(CommunityMember $member, CommunitySinglePurchase $purchase): CommunityPaywallAccess
    {
        $member->loadMissing(['community', 'user']);
        if ($member->community_id !== $purchase->community_id || $member->user_id !== $purchase->user_id) {
            throw new InvalidArgumentException('Purchase does not belong to the supplied community member.');
        }

        $tierId = $this->resolveTierIdFromPurchase($purchase);
        $expiresAt = Arr::get($purchase->metadata, 'access_expires_at');
        $grantedBy = Arr::get($purchase->metadata, 'granted_by');

        return DB::transaction(function () use ($member, $tierId, $expiresAt, $purchase, $grantedBy) {
            if ($member->status !== 'active') {
                $member->forceFill(['status' => 'active'])->save();
            }

            $access = CommunityPaywallAccessModel::updateOrCreate(
                [
                    'community_id' => $member->community_id,
                    'user_id' => $member->user_id,
                    'subscription_tier_id' => $tierId,
                ],
                [
                    'access_starts_at' => CarbonImmutable::now(),
                    'access_ends_at' => $expiresAt ? CarbonImmutable::parse($expiresAt) : null,
                    'granted_by' => $grantedBy,
                    'reason' => CommunityPaywallAccessGrantedBy::SINGLE_PURCHASE->value,
                    'metadata' => [
                        'purchase_id' => $purchase->getKey(),
                        'provider' => $purchase->provider,
                        'provider_reference' => $purchase->provider_reference,
                    ],
                ]
            );

            return $access->refresh();
        });
    }

    public function revokeAccess(CommunityPaywallAccess $access): void
    {
        $access->loadMissing(['user']);

        $access->forceFill([
            'access_ends_at' => CarbonImmutable::now(),
            'reason' => $access->reason ?: 'revoked',
            'metadata' => array_merge($access->metadata ?? [], [
                'revoked_at' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ])->save();
    }

    public function grantSubscriptionAccess(CommunitySubscription $subscription): CommunityPaywallAccess
    {
        $subscription->loadMissing(['community', 'user', 'tier']);
        $member = CommunityMember::query()
            ->where('community_id', $subscription->community_id)
            ->where('user_id', $subscription->user_id)
            ->first();

        if ($member === null) {
            throw new ModelNotFoundException('Community member is required before granting subscription access.');
        }

        $member->loadMissing('community');

        $expiresAt = $subscription->ended_at
            ?? $subscription->renews_at
            ?? Arr::get($subscription->metadata, 'access_expires_at');

        return DB::transaction(function () use ($subscription, $member, $expiresAt) {
            $access = CommunityPaywallAccessModel::updateOrCreate(
                [
                    'community_id' => $subscription->community_id,
                    'user_id' => $subscription->user_id,
                    'subscription_tier_id' => $subscription->subscription_tier_id,
                ],
                [
                    'access_starts_at' => CarbonImmutable::now(),
                    'access_ends_at' => $expiresAt ? CarbonImmutable::parse($expiresAt) : null,
                    'granted_by' => $subscription->metadata['granted_by'] ?? null,
                    'reason' => CommunityPaywallAccessGrantedBy::TIER->value,
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'subscription_id' => $subscription->getKey(),
                        'status' => $subscription->status,
                    ]),
                ]
            );

            if ($member->status !== 'active') {
                $member->forceFill(['status' => 'active'])->save();
            }

            return $access->refresh();
        });
    }

    public function configureDefaultTier(Community $community, ?CommunitySubscriptionTier $tier): void
    {
        $community->refresh();
        $settings = $community->settings ?? [];

        Arr::set($settings, 'paywall.default_tier_id', $tier?->getKey());
        Arr::set($settings, 'paywall.default_tier_slug', $tier?->slug);
        Arr::set($settings, 'paywall.updated_at', CarbonImmutable::now()->toIso8601String());

        $community->forceFill(['settings' => $settings])->save();
    }

    private function resolveTierIdFromPurchase(CommunitySinglePurchase $purchase): ?int
    {
        if ($purchase->purchasable_type === CommunitySubscriptionTierModel::class || $purchase->purchasable instanceof CommunitySubscriptionTierModel) {
            return (int) $purchase->purchasable_id;
        }

        if ($purchase->purchasable_type === CommunitySubscriptionModel::class || $purchase->purchasable instanceof CommunitySubscriptionModel) {
            $subscription = $purchase->purchasable instanceof CommunitySubscriptionModel
                ? $purchase->purchasable
                : CommunitySubscriptionModel::query()->findOrFail($purchase->purchasable_id);

            return (int) $subscription->subscription_tier_id;
        }

        if ($purchase->purchasable_type === CommunitySinglePurchaseModel::class) {
            $single = CommunitySinglePurchaseModel::query()->findOrFail($purchase->purchasable_id);

            return $this->resolveTierIdFromPurchase($single);
        }

        return null;
    }
}
