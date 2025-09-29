<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\Community\CommunitySubscriptionStatus;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use Illuminate\Support\Collection;

/**
 * Contract bridging billing provider lifecycle events with entitlements.
 */
interface SubscriptionService
{
    public function startSubscription(CommunityMember $member, CommunitySubscriptionTier $tier, array $providerPayload = []): CommunitySubscription;

    public function cancelSubscription(CommunitySubscription $subscription, bool $immediate = false): void;

    public function syncSubscriptionStatus(CommunitySubscription $subscription, CommunitySubscriptionStatus $status, array $providerPayload = []): CommunitySubscription;

    public function listSubscriptions(Community $community, array $filters = []): Collection;
}
