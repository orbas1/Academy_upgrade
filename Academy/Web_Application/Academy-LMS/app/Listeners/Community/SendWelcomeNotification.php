<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\MemberApproved;
use App\Events\Community\MemberJoined;
use App\Jobs\Community\DistributeNotification;
use App\Jobs\Community\RebuildCommunityCounters;

class SendWelcomeNotification
{
    public function handle(MemberJoined|MemberApproved $event): void
    {
        $member = $event->member;

        DistributeNotification::dispatch([
            'community_id' => $member->community_id,
            'event' => $event instanceof MemberApproved ? 'member.approved' : 'member.joined',
            'recipient_ids' => [$member->user_id],
            'data' => [
                'subject' => 'Welcome to the community',
                'message' => 'Thanks for joining! Tap to explore the latest posts and introduce yourself.',
                'member_id' => $member->getKey(),
            ],
        ]);

        RebuildCommunityCounters::dispatch([
            'community_id' => $member->community_id,
        ]);
    }
}
