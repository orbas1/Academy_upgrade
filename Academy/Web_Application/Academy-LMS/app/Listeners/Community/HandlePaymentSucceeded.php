<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\PaymentSucceeded;
use App\Jobs\Community\DistributeNotification;
use App\Jobs\Community\ReindexCommunitySearch;
use App\Models\Community\CommunitySubscription;

class HandlePaymentSucceeded
{
    public function handle(PaymentSucceeded $event): void
    {
        $member = $event->member;
        $purchase = $event->purchase;

        DistributeNotification::dispatch([
            'community_id' => $member->community_id,
            'event' => 'payment.succeeded',
            'recipient_ids' => [$member->user_id],
            'data' => [
                'subject' => 'Payment confirmed',
                'message' => 'Your purchase is complete. You now have access to the selected community benefits.',
                'member_id' => $member->getKey(),
                'purchase_type' => $purchase ? class_basename($purchase) : null,
                'purchase_id' => $purchase?->getKey(),
            ],
        ]);

        if ($purchase instanceof CommunitySubscription) {
            ReindexCommunitySearch::dispatch([
                'model' => $purchase::class,
                'id' => $purchase->getKey(),
            ]);
        }
    }
}
