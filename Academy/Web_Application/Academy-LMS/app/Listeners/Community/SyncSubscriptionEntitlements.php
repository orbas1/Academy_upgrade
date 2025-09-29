<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\SubscriptionStarted;
use App\Jobs\Community\RebuildCommunityCounters;
use App\Jobs\Community\ReindexCommunitySearch;

class SyncSubscriptionEntitlements
{
    public function handle(SubscriptionStarted $event): void
    {
        $member = $event->member;

        if ($member->status !== 'active') {
            $member->forceFill(['status' => 'active'])->save();
        }

        ReindexCommunitySearch::dispatch([
            'model' => $event->subscription::class,
            'id' => $event->subscription->getKey(),
        ]);

        RebuildCommunityCounters::dispatch([
            'community_id' => $member->community_id,
        ]);
    }
}
