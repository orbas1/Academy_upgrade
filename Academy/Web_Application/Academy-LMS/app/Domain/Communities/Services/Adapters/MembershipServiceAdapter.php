<?php

namespace App\Domain\Communities\Services\Adapters;

use App\Domain\Communities\Contracts\MembershipService as MembershipContract;
use App\Domain\Communities\Exceptions\PendingServiceException;
use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use App\Services\Community\MembershipService as LegacyMembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use function sprintf;

final class MembershipServiceAdapter implements MembershipContract
{
    public function __construct(
        private readonly LegacyMembershipService $membershipService
    ) {
    }

    public function join(int $communityId, int $userId, array $context = []): array
    {
        $community = Community::query()->findOrFail($communityId);
        $user = User::query()->findOrFail($userId);

        $member = $this->membershipService->requestJoin(
            $user,
            $community,
            Arr::get($context, 'message')
        );

        return $this->formatMemberSnapshot($member);
    }

    public function approve(int $communityId, int $userId, int $actorId): array
    {
        $member = $this->resolveMember($communityId, $userId);
        $actor = $this->resolveActor($communityId, $actorId);

        $approved = $this->membershipService->approveMember($member, $actor);
        $moderation = $this->extractModerationRecord($approved, 'approved');

        return [
            'community_id' => $approved->community_id,
            'user_id' => $approved->user_id,
            'status' => $approved->status,
            'role' => $approved->role,
            'approved_by' => $moderation['actor_id'] ?? $actor->getKey(),
            'approved_at' => Carbon::parse($moderation['approved_at'] ?? $approved->updated_at),
        ];
    }

    public function leave(int $communityId, int $userId): void
    {
        $member = $this->resolveMember($communityId, $userId);

        $this->membershipService->leaveCommunity($member);
    }

    public function ban(int $communityId, int $userId, array $context): array
    {
        $member = $this->resolveMember($communityId, $userId);
        $actorId = Arr::get($context, 'actor_id');

        if ($actorId === null) {
            throw PendingServiceException::for(static::class, __METHOD__);
        }

        $actor = $this->resolveActor($communityId, (int) $actorId);
        $this->membershipService->banMember($member, $actor, Arr::get($context, 'reason'));

        $fresh = $member->fresh();
        $moderation = $this->extractModerationRecord($fresh, 'banned');

        return [
            'community_id' => $fresh->community_id,
            'user_id' => $fresh->user_id,
            'status' => $fresh->status,
            'role' => $fresh->role,
            'ban_reason' => $moderation['reason'] ?? Arr::get($context, 'reason'),
            'ban_expires_at' => isset($context['expires_at'])
                ? Carbon::parse($context['expires_at'])
                : null,
        ];
    }

    public function promote(int $communityId, int $userId, string $role, int $actorId): array
    {
        $member = $this->resolveMember($communityId, $userId);
        $actor = $this->resolveActor($communityId, $actorId);

        $targetRole = CommunityMemberRole::from($role);
        $updated = $this->membershipService->promoteMember($member, $targetRole, $actor);

        $history = collect($this->extractModerationRecord($updated, 'role_updates', []));
        $latest = $history->last();

        return [
            'community_id' => $updated->community_id,
            'user_id' => $updated->user_id,
            'role' => $updated->role,
            'changed_by' => $latest['actor_id'] ?? $actor->getKey(),
            'changed_at' => Carbon::parse($latest['updated_at'] ?? $updated->updated_at),
        ];
    }

    public function computeRoleChecks(int $communityId, int $userId): array
    {
        $member = $this->resolveMember($communityId, $userId);
        $role = CommunityMemberRole::from($member->role);

        return [
            'is_owner' => $role === CommunityMemberRole::OWNER,
            'is_admin' => in_array($role, [CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN], true),
            'is_moderator' => in_array($role, [CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN, CommunityMemberRole::MODERATOR], true),
            'can_post' => $member->status === CommunityMemberStatus::ACTIVE->value,
            'can_comment' => $member->status === CommunityMemberStatus::ACTIVE->value,
            'can_manage_members' => in_array($role, [CommunityMemberRole::OWNER, CommunityMemberRole::ADMIN], true),
            'can_manage_paywall' => $role === CommunityMemberRole::OWNER,
        ];
    }

    public function touchLastSeen(int $communityId, int $userId, ?Carbon $at = null): void
    {
        $member = $this->resolveMember($communityId, $userId);

        $moment = $at
            ? CarbonImmutable::instance($at)
            : CarbonImmutable::now();

        $this->membershipService->recordLastSeen($member, $moment);
    }

    public function heartbeat(int $communityId, int $userId, ?Carbon $at = null): void
    {
        $member = $this->resolveMember($communityId, $userId);

        $member->is_online = true;
        $member->last_seen_at = ($at ?? Carbon::now());
        $member->save();
    }

    private function resolveMember(int $communityId, int $userId): CommunityMember
    {
        return CommunityMember::query()
            ->where('community_id', $communityId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function resolveActor(int $communityId, int $actorId): User
    {
        $actor = User::query()->findOrFail($actorId);

        $this->ensureActorBelongsToCommunity($communityId, $actorId);

        return $actor;
    }

    private function ensureActorBelongsToCommunity(int $communityId, int $actorId): void
    {
        $membership = CommunityMember::query()
            ->where('community_id', $communityId)
            ->where('user_id', $actorId)
            ->first();

        if (! $membership) {
            $exception = new ModelNotFoundException(sprintf('Actor %d is not authorised for community %d.', $actorId, $communityId));
            $exception->setModel(CommunityMember::class, [$actorId]);

            throw $exception;
        }
    }

    private function formatMemberSnapshot(CommunityMember $member): array
    {
        return [
            'community_id' => $member->community_id,
            'user_id' => $member->user_id,
            'status' => $member->status,
            'role' => $member->role,
            'requested_at' => Carbon::parse($member->created_at ?? $member->joined_at),
            'last_seen_at' => $member->last_seen_at ? Carbon::parse($member->last_seen_at) : null,
            'is_online' => (bool) $member->is_online,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractModerationRecord(CommunityMember $member, string $key, $default = []): array
    {
        $metadata = $member->metadata ?? [];
        $moderation = Arr::get($metadata, 'moderation');

        if (! is_array($moderation)) {
            return $default;
        }

        $record = Arr::get($moderation, $key, $default);

        return is_array($record) ? $record : $default;
    }
}

