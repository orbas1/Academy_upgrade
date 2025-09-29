<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullPaywallService implements PaywallService
{
    use NotImplemented;
    public function canAccessPost(\App\Models\Community\CommunityPost $post, ?\App\Models\Community\CommunityMember $member): bool
    {
        $this->notImplemented();
    }

    public function grantSinglePurchase(\App\Models\Community\CommunityMember $member, \App\Models\Community\CommunitySinglePurchase $purchase): \App\Models\Community\CommunityPaywallAccess
    {
        $this->notImplemented();
    }

    public function revokeAccess(\App\Models\Community\CommunityPaywallAccess $access): void
    {
        $this->notImplemented();
    }

    public function grantSubscriptionAccess(\App\Models\Community\CommunitySubscription $subscription): \App\Models\Community\CommunityPaywallAccess
    {
        $this->notImplemented();
    }

    public function configureDefaultTier(\App\Models\Community\Community $community, ?\App\Models\Community\CommunitySubscriptionTier $tier): void
    {
        $this->notImplemented();
    }
}
