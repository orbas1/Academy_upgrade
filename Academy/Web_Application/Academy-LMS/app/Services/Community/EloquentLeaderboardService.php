<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityLeaderboardService as DomainLeaderboardService;
use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityLeaderboard;
use App\Models\Community\CommunityPointsLedger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentLeaderboardService implements LeaderboardService
{
    public function __construct(private readonly DomainLeaderboardService $leaderboards)
    {
    }

    public function snapshot(Community $community, CommunityLeaderboardPeriod $period): CommunityLeaderboard
    {
        $limit = (int) config('communities.leaderboards.default_limit', 50);

        return $this->leaderboards->generate($community, $period->value, $limit);
    }

    public function getLeaderboard(Community $community, CommunityLeaderboardPeriod $period, int $limit = 25): Collection
    {
        $record = $this->resolveLeaderboard($community, $period);

        $entries = collect($record?->entries ?? [])
            ->map(function (array $entry, int $index): array {
                return [
                    'member_id' => (int) $entry['member_id'],
                    'rank' => $index + 1,
                    'points' => (int) $entry['points'],
                    'display_name' => $entry['display_name'] ?? null,
                ];
            });

        return $entries->take($limit)->values();
    }

    public function getMemberStanding(CommunityMember $member, CommunityLeaderboardPeriod $period): ?array
    {
        $record = $this->resolveLeaderboard($member->community, $period);
        $entries = collect($record?->entries ?? []);

        $index = $entries->search(fn (array $entry) => (int) $entry['member_id'] === $member->getKey());
        if ($index !== false) {
            $entry = $entries[$index];

            return [
                'rank' => $index + 1,
                'points' => (int) ($entry['points'] ?? 0),
                'display_name' => $entry['display_name'] ?? $member->user?->name,
            ];
        }

        $points = $this->calculateMemberPoints($member, $period);
        if ($points === 0) {
            return null;
        }

        return [
            'rank' => null,
            'points' => $points,
            'display_name' => $member->user?->name,
        ];
    }

    protected function resolveLeaderboard(Community $community, CommunityLeaderboardPeriod $period): ?CommunityLeaderboard
    {
        $cacheKey = sprintf('community:%d:leaderboard:%s', $community->getKey(), $period->value);
        $ttl = (int) config('communities.leaderboards.cache_seconds', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($community, $period) {
            $record = CommunityLeaderboard::query()
                ->where('community_id', $community->getKey())
                ->where('period', $period->value)
                ->orderByDesc('starts_on')
                ->first();

            if ($this->shouldRegenerate($record, $period)) {
                $record = $this->snapshot($community, $period);
            }

            return $record;
        });
    }

    protected function shouldRegenerate(?CommunityLeaderboard $leaderboard, CommunityLeaderboardPeriod $period): bool
    {
        if ($leaderboard === null) {
            return true;
        }

        $metadata = $leaderboard->metadata ?? [];
        $generatedAt = isset($metadata['generated_at'])
            ? CarbonImmutable::parse($metadata['generated_at'])
            : null;

        $refreshInterval = (int) config('communities.leaderboards.refresh_minutes', 30);
        if ($generatedAt && $generatedAt->addMinutes($refreshInterval)->isFuture()) {
            return false;
        }

        [$start, $end] = $this->periodRange($period);
        if ($end && $leaderboard->ends_on && CarbonImmutable::parse($leaderboard->ends_on)->lt($end)) {
            return true;
        }

        if ($period === CommunityLeaderboardPeriod::ALLTIME) {
            return $generatedAt === null;
        }

        return true;
    }

    protected function periodRange(CommunityLeaderboardPeriod $period): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            CommunityLeaderboardPeriod::DAILY => [$today, $today],
            CommunityLeaderboardPeriod::WEEKLY => [$today->startOfWeek(), $today->endOfWeek()],
            CommunityLeaderboardPeriod::MONTHLY => [$today->startOfMonth(), $today->endOfMonth()],
            CommunityLeaderboardPeriod::ALLTIME => [null, null],
        };
    }

    protected function calculateMemberPoints(CommunityMember $member, CommunityLeaderboardPeriod $period): int
    {
        [$start, $end] = $this->periodRange($period);

        $query = CommunityPointsLedger::query()
            ->where('community_id', $member->community_id)
            ->where('member_id', $member->getKey());

        if ($start) {
            $query->where('occurred_at', '>=', $start->startOfDay());
        }

        if ($end) {
            $query->where('occurred_at', '<=', $end->endOfDay());
        }

        return (int) $query->sum('points_delta');
    }
}
