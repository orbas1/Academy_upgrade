<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPointsRule;
use App\Events\Community\PointsAwarded;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityPointsService
{
    public function awardPoints(CommunityMember $member, string $action, int $points, ?User $actor = null, ?array $metadata = null): CommunityPointsLedger
    {
        return DB::transaction(function () use ($member, $action, $points, $actor, $metadata) {
            $newBalance = max(0, $member->points + $points);
            $member->points = $newBalance;
            $member->lifetime_points += max(0, $points);
            $member->save();

            $metadataPayload = is_array($metadata) ? $metadata : null;

            $entry = CommunityPointsLedger::create([
                'community_id' => $member->community_id,
                'member_id' => $member->getKey(),
                'action' => $action,
                'points_delta' => $points,
                'balance_after' => $newBalance,
                'source_type' => $metadataPayload['source_type'] ?? null,
                'source_id' => $metadataPayload['source_id'] ?? null,
                'acted_by' => $actor?->getKey(),
                'occurred_at' => CarbonImmutable::now(),
                'metadata' => $metadataPayload,
            ]);

            Log::info('community.points.awarded', [
                'community_id' => $member->community_id,
                'member_id' => $member->getKey(),
                'action' => $action,
                'delta' => $points,
                'balance_after' => $newBalance,
            ]);

            event(new PointsAwarded($member->fresh(), $entry, $points, $action, $actor, $metadataPayload ?? []));

            return $entry;
        });
    }

    public function applyRule(CommunityPointsRule $rule, CommunityMember $member, array $context = []): ?CommunityPointsLedger
    {
        if (!$rule->is_active) {
            return null;
        }

        $points = (int)($rule->points);
        $cap = $rule->metadata['daily_cap'] ?? null;

        if ($cap !== null) {
            $today = CarbonImmutable::today();
            $awardedToday = CommunityPointsLedger::query()
                ->where('member_id', $member->getKey())
                ->where('action', $rule->action)
                ->whereBetween('occurred_at', [$today->startOfDay(), $today->endOfDay()])
                ->sum('points_delta');

            if ($awardedToday >= $cap) {
                return null;
            }
        }

        return $this->awardPoints($member, $rule->action, $points, $context['actor'] ?? null, [
            'rule_id' => $rule->getKey(),
            'context' => $context,
        ]);
    }

    public function recalculateMember(CommunityMember $member): void
    {
        $latest = CommunityPointsLedger::query()
            ->where('member_id', $member->getKey())
            ->orderByDesc('occurred_at')
            ->value('balance_after');

        $member->points = $latest ?? 0;
        $member->lifetime_points = CommunityPointsLedger::query()
            ->where('member_id', $member->getKey())
            ->where('points_delta', '>', 0)
            ->sum('points_delta');
        $member->save();
    }

    public function recalculateCommunity(Community $community): void
    {
        $community->members
            ->each(fn (CommunityMember $member) => $this->recalculateMember($member));
    }
}

