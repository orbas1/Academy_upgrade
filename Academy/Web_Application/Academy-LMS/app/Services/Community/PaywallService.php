<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPaywallAccess;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunitySinglePurchase;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;

/**
 * Contract for enforcing community paywall and entitlement logic.
 */
interface PaywallService
{
    public function canAccessPost(CommunityPost $post, ?CommunityMember $member): bool;

    public function grantSinglePurchase(CommunityMember $member, CommunitySinglePurchase $purchase): CommunityPaywallAccess;

    public function revokeAccess(CommunityPaywallAccess $access): void;

    public function grantSubscriptionAccess(CommunitySubscription $subscription): CommunityPaywallAccess;

    public function configureDefaultTier(Community $community, ?CommunitySubscriptionTier $tier): void;
}
