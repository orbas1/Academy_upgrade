<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullSubscriptionService implements SubscriptionService
{
    use NotImplemented;
    public function startSubscription(\App\Models\Community\CommunityMember $member, \App\Models\Community\CommunitySubscriptionTier $tier, array $providerPayload = []): \App\Models\Community\CommunitySubscription
    {
        $this->notImplemented();
    }

    public function cancelSubscription(\App\Models\Community\CommunitySubscription $subscription, bool $immediate = false): void
    {
        $this->notImplemented();
    }

    public function syncSubscriptionStatus(\App\Models\Community\CommunitySubscription $subscription, \App\Enums\Community\CommunitySubscriptionStatus $status, array $providerPayload = []): \App\Models\Community\CommunitySubscription
    {
        $this->notImplemented();
    }

    public function listSubscriptions(\App\Models\Community\Community $community, array $filters = []): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }
}
