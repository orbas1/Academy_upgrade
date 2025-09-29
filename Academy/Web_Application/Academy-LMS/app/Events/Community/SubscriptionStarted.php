<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionStarted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember $member;

    public CommunitySubscription $subscription;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityMember $member, CommunitySubscription $subscription, array $context = [])
    {
        $this->member = $member;
        $this->subscription = $subscription;
        $this->context = $context + [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'subscription_id' => $subscription->getKey(),
            'tier_id' => $subscription->subscription_tier_id,
        ];
    }
}
