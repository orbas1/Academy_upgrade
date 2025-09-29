<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySinglePurchase;
use App\Models\Community\CommunitySubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember $member;

    public CommunitySubscription|CommunitySinglePurchase|null $purchase;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(
        CommunityMember $member,
        CommunitySubscription|CommunitySinglePurchase|null $purchase = null,
        array $context = []
    ) {
        $this->member = $member;
        $this->purchase = $purchase;
        $this->context = $context + [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'purchase_id' => $purchase?->getKey(),
            'purchase_type' => $purchase ? $purchase::class : null,
        ];
    }
}
