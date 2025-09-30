<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityPointsService as DomainPointsService;
use App\Enums\Community\CommunityPointsEvent;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPointsLedger;
use App\Models\Community\CommunityPointsRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EloquentPointsService implements PointsService
{
    public function __construct(private readonly DomainPointsService $points)
    {
    }

    public function awardPoints(CommunityMember $member, CommunityPointsEvent $event, array $context = []): CommunityPointsLedger
    {
        $rule = CommunityPointsRule::query()
            ->where('community_id', $member->community_id)
            ->where('action', $event->value)
            ->where('is_active', true)
            ->first();

        if ($rule) {
            $ledger = $this->points->applyRule($rule, $member, $context);
            if ($ledger) {
                return $ledger;
            }
        }

        if (! array_key_exists('points', $context)) {
            throw new InvalidArgumentException('Context must include a points value when no active rule exists.');
        }

        $points = (int) $context['points'];
        $actor = $context['actor'] ?? null;
        $metadata = $context['metadata'] ?? [];

        return $this->points->awardPoints($member, $event->value, $points, $actor, $metadata);
    }

    public function revokePoints(CommunityPointsLedger $ledger, ?string $reason = null): void
    {
        DB::transaction(function () use ($ledger, $reason): void {
            $member = CommunityMember::query()->findOrFail($ledger->member_id);
            $member->points = max(0, $member->points - $ledger->points_delta);
            $member->save();

            CommunityPointsLedger::create([
                'community_id' => $ledger->community_id,
                'member_id' => $ledger->member_id,
                'action' => Str::finish($ledger->action, ':revoke'),
                'points_delta' => -1 * $ledger->points_delta,
                'balance_after' => $member->points,
                'source_type' => $ledger->source_type,
                'source_id' => $ledger->source_id,
                'acted_by' => $ledger->acted_by,
                'occurred_at' => now(),
                'metadata' => [
                    'revoked_ledger_id' => $ledger->getKey(),
                    'reason' => $reason,
                ],
            ]);
        });
    }

    public function getBalance(CommunityMember $member): int
    {
        return (int) $member->fresh()->points;
    }

    public function getAvailableRules(Community $community): Collection
    {
        return CommunityPointsRule::query()
            ->where('community_id', $community->getKey())
            ->orderBy('action')
            ->get();
    }

    public function syncRules(Community $community, Collection $rules): void
    {
        $rules->each(function (array $payload) use ($community): void {
            CommunityPointsRule::updateOrCreate(
                [
                    'community_id' => $community->getKey(),
                    'action' => $payload['action'],
                ],
                [
                    'points' => (int) $payload['points'],
                    'cooldown_seconds' => (int) ($payload['cooldown_seconds'] ?? 0),
                    'is_active' => (bool) ($payload['is_active'] ?? true),
                    'metadata' => $payload['metadata'] ?? [],
                ]
            );
        });
    }
}
