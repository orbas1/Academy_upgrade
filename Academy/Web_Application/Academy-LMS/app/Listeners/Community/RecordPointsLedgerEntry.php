<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\PointsAwarded;
use App\Jobs\Community\GenerateLeaderboardSnapshot;
use App\Jobs\Community\ReindexCommunitySearch;

class RecordPointsLedgerEntry
{
    public function handle(PointsAwarded $event): void
    {
        $member = $event->member;

        GenerateLeaderboardSnapshot::dispatch([
            'community_id' => $member->community_id,
            'period' => 'daily',
            'as_of' => now()->toIso8601String(),
            'limit' => 25,
        ]);

        ReindexCommunitySearch::dispatch([
            'model' => $member::class,
            'id' => $member->getKey(),
        ]);
    }
}
