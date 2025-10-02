<?php

namespace App\Domain\Communities\Services\Adapters;

use App\Domain\Communities\Contracts\PointsService as PointsContract;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPointsRule;
use App\Domain\Communities\Services\CommunityPointsService;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class PointsServiceAdapter implements PointsContract
{
    public function __construct(
        private readonly CommunityPointsService $points
    ) {
    }

    public function award(int $userId, string $event, int $points, array $context = []): array
    {
        $member = $this->resolveMember($userId, $context);
        $actor = $this->resolveActor($context);

        $rule = $this->resolveRule($member->community_id, $event);
        $capped = false;
        $ledger = null;

        $payloadContext = [
            'actor' => $actor,
            'metadata' => $this->extractMetadata($context),
            'context' => Arr::except($context, ['actor_id', 'metadata', 'source', 'community_id', 'member_id']),
        ];

        if ($rule) {
            $ledger = $this->points->applyRule($rule, $member, $payloadContext);
            $capped = $ledger === null;
        }

        if (! $ledger && ! $capped) {
            $ledger = $this->points->awardPoints(
                $member,
                $event,
                $points,
                $actor,
                $this->extractMetadata($context)
            );
        }

        if (! $ledger) {
            return [
                'user_id' => $member->user_id,
                'community_id' => $member->community_id,
                'event' => $event,
                'points' => 0,
                'total' => (int) $member->fresh()->points,
                'awarded_at' => Carbon::now(),
                'capped' => true,
            ];
        }

        return [
            'user_id' => $member->user_id,
            'community_id' => $ledger->community_id,
            'event' => $event,
            'points' => (int) $ledger->points_delta,
            'total' => (int) $ledger->balance_after,
            'awarded_at' => Carbon::parse($ledger->occurred_at),
            'capped' => $capped,
        ];
    }

    public function remainingForToday(int $userId, string $event): array
    {
        $rule = CommunityPointsRule::query()
            ->whereNull('community_id')
            ->where('action', $event)
            ->where('is_active', true)
            ->first();

        $cap = $rule?->metadata['daily_cap'] ?? null;
        if ($cap === null) {
            $cap = $rule?->metadata['max_per_day'] ?? null;
        }

        $today = CarbonImmutable::today();

        $awarded = CommunityPointsLedger::query()
            ->where('action', $event)
            ->whereBetween('occurred_at', [$today->startOfDay(), $today->endOfDay()])
            ->whereHas('member', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->sum('points_delta');

        $remaining = $cap !== null
            ? max(0, (int) $cap - (int) $awarded)
            : PHP_INT_MAX;

        return [
            'event' => $event,
            'remaining' => $remaining,
            'reset_at' => CarbonImmutable::tomorrow(),
        ];
    }

    private function resolveMember(int $userId, array $context): CommunityMember
    {
        if ($memberId = Arr::get($context, 'member_id')) {
            /** @var CommunityMember|null $member */
            $member = CommunityMember::query()->find($memberId);

            if (! $member) {
                throw (new ModelNotFoundException())->setModel(CommunityMember::class, [$memberId]);
            }

            if ($member->user_id !== $userId) {
                throw new InvalidArgumentException('Member does not belong to the provided user.');
            }

            return $member;
        }

        $communityId = Arr::get($context, 'community_id');

        if (! $communityId) {
            throw new InvalidArgumentException('community_id or member_id context is required for awarding points.');
        }

        /** @var CommunityMember $member */
        $member = CommunityMember::query()
            ->where('community_id', $communityId)
            ->where('user_id', $userId)
            ->firstOrFail();

        return $member;
    }

    private function resolveActor(array $context): ?User
    {
        $actorId = Arr::get($context, 'actor_id');

        if (! $actorId) {
            return null;
        }

        /** @var User $actor */
        $actor = User::query()->findOrFail((int) $actorId);

        return $actor;
    }

    private function resolveRule(?int $communityId, string $event): ?CommunityPointsRule
    {
        $scoped = CommunityPointsRule::query()
            ->where('action', $event)
            ->where('is_active', true);

        if ($communityId !== null) {
            $rule = (clone $scoped)
                ->where('community_id', $communityId)
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        return $scoped
            ->whereNull('community_id')
            ->first();
    }

    private function extractMetadata(array $context): ?array
    {
        $metadata = Arr::get($context, 'metadata', []);

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $source = Arr::get($context, 'source');

        if (is_array($source)) {
            if (isset($source['type'])) {
                $metadata['source_type'] = $source['type'];
            }

            if (isset($source['id'])) {
                $metadata['source_id'] = $source['id'];
            }
        }

        return $metadata ?: null;
    }
}
