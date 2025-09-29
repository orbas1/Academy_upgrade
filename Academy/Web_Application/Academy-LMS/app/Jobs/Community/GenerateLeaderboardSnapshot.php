<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Models\Community\CommunityLeaderboard;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPointsLedger;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class GenerateLeaderboardSnapshot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public string $queue = 'leaderboards';

    public int $tries = 3;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $communityId = (int) Arr::get($this->payload, 'community_id');

        if ($communityId <= 0) {
            Log::warning('community.leaderboard.snapshot.skipped', ['reason' => 'missing_community', 'payload' => $this->payload]);

            return;
        }

        $period = (string) Arr::get($this->payload, 'period', 'weekly');
        $asOf = CarbonImmutable::parse(Arr::get($this->payload, 'as_of', CarbonImmutable::now()->toIso8601String()));

        [$startsOn, $endsOn] = $this->resolvePeriodBounds($period, $asOf);
        $limit = (int) Arr::get($this->payload, 'limit', 50);

        $entries = CommunityPointsLedger::query()
            ->selectRaw('member_id, SUM(points_delta) as points, MAX(occurred_at) as last_event_at')
            ->where('community_id', $communityId)
            ->when($startsOn, fn ($query) => $query->where('occurred_at', '>=', $startsOn))
            ->when($endsOn, fn ($query) => $query->where('occurred_at', '<=', $endsOn))
            ->groupBy('member_id')
            ->orderByDesc('points')
            ->limit($limit)
            ->get();

        if ($entries->isEmpty()) {
            Log::debug('community.leaderboard.snapshot.empty', [
                'community_id' => $communityId,
                'period' => $period,
            ]);

            return;
        }

        $members = CommunityMember::query()
            ->with('user:id,name,avatar')
            ->whereIn('id', $entries->pluck('member_id'))
            ->get()
            ->keyBy('id');

        $payloadEntries = $entries
            ->map(fn ($row, $index) => $this->mapEntry($row->member_id, (int) $row->points, $members, $index + 1, $row->last_event_at))
            ->filter()
            ->values()
            ->all();

        CommunityLeaderboard::updateOrCreate(
            [
                'community_id' => $communityId,
                'period' => $period,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
            ],
            [
                'entries' => $payloadEntries,
                'metadata' => [
                    'generated_at' => $asOf->toIso8601String(),
                    'source' => 'points_ledger',
                ],
            ]
        );

        Log::info('community.leaderboard.snapshot.generated', [
            'community_id' => $communityId,
            'period' => $period,
            'entries' => count($payloadEntries),
        ]);
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    protected function resolvePeriodBounds(string $period, CarbonImmutable $asOf): array
    {
        return match ($period) {
            'daily' => [$asOf->startOfDay(), $asOf->endOfDay()],
            'weekly' => [$asOf->startOfWeek(), $asOf->endOfWeek()],
            'monthly' => [$asOf->startOfMonth(), $asOf->endOfMonth()],
            'all_time' => [null, null],
            default => [$asOf->startOfWeek(), $asOf->endOfWeek()],
        };
    }

    /**
     * @param EloquentCollection<int, CommunityMember> $members
     * @return array<string, mixed>|null
     */
    protected function mapEntry(int $memberId, int $points, EloquentCollection $members, int $rank, ?string $lastEventAt): ?array
    {
        /** @var CommunityMember|null $member */
        $member = $members->get($memberId);

        if ($member === null) {
            return null;
        }

        return [
            'member_id' => $memberId,
            'user_id' => $member->user_id,
            'display_name' => $member->user?->name,
            'avatar' => $member->user?->avatar_path ?? null,
            'points' => $points,
            'rank' => $rank,
            'last_event_at' => $lastEventAt,
        ];
    }
}
