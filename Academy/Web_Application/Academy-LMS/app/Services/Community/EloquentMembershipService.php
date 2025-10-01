<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Analytics\Services\AnalyticsDispatcher;
use App\Events\Community\MemberApproved;
use App\Domain\Communities\Services\CommunityMembershipService;
use App\Enums\Community\CommunityJoinPolicy;
use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EloquentMembershipService implements MembershipService
{
    public function __construct(
        private readonly CommunityMembershipService $memberships,
        private readonly AnalyticsDispatcher $analytics
    )
    {
    }

    public function requestJoin(User $user, Community $community, ?string $message = null): CommunityMember
    {
        $policy = CommunityJoinPolicy::from($community->join_policy ?? CommunityJoinPolicy::OPEN->value);
        $autoApprove = $policy === CommunityJoinPolicy::OPEN;

        $member = $this->memberships->joinCommunity(
            $community,
            $user,
            CommunityMemberRole::MEMBER->value,
            $autoApprove
        );

        if ($message !== null && trim($message) !== '') {
            $metadata = $member->metadata ?? [];
            $metadata['join_request'] = [
                'message' => Str::limit($message, 1000),
                'submitted_at' => CarbonImmutable::now()->toIso8601String(),
            ];
            $member->metadata = $metadata;
            $member->save();
        }

        $fresh = $member->fresh(['user', 'community']);

        $this->analytics->record('community_join', $user, [
            'community_id' => $community->getKey(),
            'auto_approved' => $autoApprove,
        ], $community);

        return $fresh;
    }

    public function approveMember(CommunityMember $member, User $actor): CommunityMember
    {
        $this->assertSameCommunity($member, $actor);

        $member = $this->memberships->updateStatus($member, CommunityMemberStatus::ACTIVE->value);
        $metadata = $member->metadata ?? [];
        $metadata['moderation']['approved'] = [
            'actor_id' => $actor->getKey(),
            'approved_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $member->metadata = $metadata;
        $member->save();

        $fresh = $member->fresh(['user', 'community']);

        event(new MemberApproved($fresh, $actor));

        $this->analytics->record('member_approved', $actor, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
        ], $fresh->community);

        return $fresh;
    }

    public function denyMember(CommunityMember $member, User $actor, ?string $reason = null): void
    {
        $this->assertSameCommunity($member, $actor);

        $metadata = $member->metadata ?? [];
        $metadata['moderation']['denied'] = [
            'actor_id' => $actor->getKey(),
            'reason' => $reason ? Str::limit($reason, 500) : null,
            'denied_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $member->metadata = $metadata;
        $member->save();

        $this->memberships->updateStatus($member, CommunityMemberStatus::LEFT->value);
    }

    public function leaveCommunity(CommunityMember $member): void
    {
        $this->memberships->leaveCommunity($member);

        $this->analytics->record('member_leave', $member->user, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
        ], $member->community);
    }

    public function banMember(CommunityMember $member, User $actor, ?string $reason = null): void
    {
        $this->assertSameCommunity($member, $actor);

        $metadata = $member->metadata ?? [];
        $metadata['moderation']['banned'] = [
            'actor_id' => $actor->getKey(),
            'reason' => $reason ? Str::limit($reason, 500) : null,
            'banned_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $member->metadata = $metadata;
        $member->save();

        $this->memberships->updateStatus($member, CommunityMemberStatus::BANNED->value);

        $this->analytics->record('member_banned', $actor, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'reason' => $reason,
        ], $member->community);
    }

    public function unbanMember(CommunityMember $member, User $actor): CommunityMember
    {
        $this->assertSameCommunity($member, $actor);

        $metadata = $member->metadata ?? [];
        $metadata['moderation']['banned'] = array_merge(
            $metadata['moderation']['banned'] ?? [],
            [
                'unbanned_at' => CarbonImmutable::now()->toIso8601String(),
                'unbanned_by' => $actor->getKey(),
            ]
        );
        $member->metadata = $metadata;
        $member->save();

        $fresh = $this->memberships->updateStatus($member, CommunityMemberStatus::ACTIVE->value)->fresh(['user', 'community']);

        $this->analytics->record('member_unbanned', $actor, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
        ], $fresh->community);

        return $fresh;
    }

    public function promoteMember(CommunityMember $member, CommunityMemberRole $targetRole, User $actor): CommunityMember
    {
        $this->assertSameCommunity($member, $actor);

        if ($member->role === $targetRole->value) {
            return $member->fresh(['user']);
        }

        $metadata = $member->metadata ?? [];
        $metadata['moderation']['role_updates'][] = [
            'actor_id' => $actor->getKey(),
            'from' => $member->role,
            'to' => $targetRole->value,
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $member->metadata = $metadata;
        $member->save();

        $updated = $this->memberships->updateRole($member, $targetRole->value)->fresh(['user', 'community']);

        $this->analytics->record('member_role_promoted', $actor, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'role' => $targetRole->value,
        ], $updated->community);

        return $updated;
    }

    public function demoteMember(CommunityMember $member, User $actor): CommunityMember
    {
        $this->assertSameCommunity($member, $actor);

        if ($member->role === CommunityMemberRole::MEMBER->value) {
            return $member->fresh(['user']);
        }

        $metadata = $member->metadata ?? [];
        $metadata['moderation']['role_updates'][] = [
            'actor_id' => $actor->getKey(),
            'from' => $member->role,
            'to' => CommunityMemberRole::MEMBER->value,
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
        $member->metadata = $metadata;
        $member->save();

        $updated = $this->memberships->updateRole($member, CommunityMemberRole::MEMBER->value)->fresh(['user', 'community']);

        $this->analytics->record('member_role_demoted', $actor, [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
        ], $updated->community);

        return $updated;
    }

    public function recordLastSeen(CommunityMember $member, CarbonInterface $seenAt): void
    {
        $this->memberships->trackPresence($member, true, $seenAt);
    }

    public function getActiveMembers(Community $community, array $filters = []): LengthAwarePaginatorContract
    {
        $query = $community->members()
            ->with('user:id,name,email,profile_photo_path,photo,role')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when(array_key_exists('online', $filters), fn ($q) => $q->where('is_online', (bool) $filters['online']))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $term = Str::lower($search);
                $q->whereHas('user', function ($builder) use ($term) {
                    $builder->whereRaw('LOWER(name) like ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(email) like ?', ["%{$term}%"]);
                });
            })
            ->when($filters['joined_after'] ?? null, fn ($q, $date) => $q->where('joined_at', '>=', CarbonImmutable::parse($date)))
            ->when($filters['joined_before'] ?? null, fn ($q, $date) => $q->where('joined_at', '<=', CarbonImmutable::parse($date)));

        $perPage = (int) Arr::get($filters, 'per_page', 25);
        $perPage = max(5, min($perPage, 100));
        $page = (int) Arr::get($filters, 'page', Paginator::resolveCurrentPage());
        $page = max($page, 1);

        $total = (clone $query)->count();

        $members = $query
            ->orderByDesc('joined_at')
            ->orderBy('user_id')
            ->forPage($page, $perPage)
            ->get();

        return new LengthAwarePaginator(
            $members,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    private function assertSameCommunity(CommunityMember $member, User $actor): void
    {
        $membership = CommunityMember::query()
            ->where('community_id', $member->community_id)
            ->where('user_id', $actor->getKey())
            ->first();

        if (! $membership) {
            Log::warning('community.membership.unauthorised_action', [
                'community_id' => $member->community_id,
                'member_id' => $member->getKey(),
                'actor_id' => $actor->getKey(),
            ]);

            throw new InvalidArgumentException('Actor must be a member of the same community.');
        }
    }
}
