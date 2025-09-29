<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityLeaderboard;
use App\Domain\Communities\Models\CommunityPointsLedger;
use Carbon\CarbonImmutable;

class CommunityLeaderboardService
{
    public function generate(Community $community, string $period = 'weekly', int $limit = 50): CommunityLeaderboard
    {
        [$start, $end] = $this->resolvePeriod($period);

        $entries = $this->buildEntries($community, $start, $end, $limit);

        return CommunityLeaderboard::updateOrCreate(
            [
                'community_id' => $community->getKey(),
                'period' => $period,
                'starts_on' => $start?->toDateString(),
                'ends_on' => $end?->toDateString(),
            ],
            [
                'entries' => $entries,
                'metadata' => [
                    'generated_at' => CarbonImmutable::now()->toIso8601String(),
                    'limit' => $limit,
                ],
            ]
        );
    }

    protected function buildEntries(Community $community, ?CarbonImmutable $start, ?CarbonImmutable $end, int $limit): array
    {
        $query = CommunityPointsLedger::query()
            ->selectRaw('community_points_ledger.member_id, SUM(points_delta) as points')
            ->join('community_members', 'community_members.id', '=', 'community_points_ledger.member_id')
            ->join('users', 'users.id', '=', 'community_members.user_id')
            ->where('community_points_ledger.community_id', $community->getKey())
            ->groupBy('community_points_ledger.member_id', 'users.name', 'users.id')
            ->orderByDesc('points')
            ->limit($limit);

        if ($start) {
            $query->where('community_points_ledger.occurred_at', '>=', $start->startOfDay());
        }

        if ($end) {
            $query->where('community_points_ledger.occurred_at', '<=', $end->endOfDay());
        }

        return $query->get()
            ->map(fn ($row) => [
                'member_id' => (int)$row->member_id,
                'points' => (int)$row->points,
                'display_name' => $row->name,
            ])
            ->values()
            ->toArray();
    }

    protected function resolvePeriod(string $period): array
    {
        $now = CarbonImmutable::today();

        return match ($period) {
            'daily' => [$now, $now],
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'monthly' => [$now->startOfMonth(), $now->endOfMonth()],
            default => [null, null],
        };
    }
}

