<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityFollow;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityMembershipService
{
    public function joinCommunity(Community $community, User $user, string $role = 'member', bool $autoApprove = true): CommunityMember
    {
        return DB::transaction(function () use ($community, $user, $role, $autoApprove) {
            $now = CarbonImmutable::now();

            $membership = CommunityMember::withTrashed()->firstOrNew([
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
            ]);

            if ($membership->exists && $membership->trashed()) {
                $membership->restore();
            }

            $membership->fill([
                'role' => $role,
                'status' => $autoApprove ? 'active' : 'pending',
                'joined_at' => $membership->joined_at ?? $now,
                'last_seen_at' => $now,
                'is_online' => true,
            ]);

            $membership->save();

            CommunityFollow::firstOrCreate(
                [
                    'follower_id' => $user->getKey(),
                    'followable_type' => Community::class,
                    'followable_id' => $community->getKey(),
                ],
                [
                    'community_id' => $community->getKey(),
                    'notifications_enabled' => true,
                    'followed_at' => $now,
                ]
            );

            Log::info('community.membership.joined', [
                'community_id' => $community->getKey(),
                'user_id' => $user->getKey(),
                'role' => $role,
                'status' => $membership->status,
            ]);

            return $membership;
        });
    }

    public function updateRole(CommunityMember $member, string $role): CommunityMember
    {
        $member->role = $role;
        $member->save();

        Log::info('community.membership.role_updated', [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'role' => $role,
        ]);

        return $member;
    }

    public function updateStatus(CommunityMember $member, string $status): CommunityMember
    {
        if ($status === 'left') {
            return $this->leaveCommunity($member);
        }

        $member->status = $status;

        if ($status === 'banned') {
            $member->is_online = false;
        }

        $member->save();

        Log::info('community.membership.status_updated', [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'status' => $status,
        ]);

        return $member;
    }

    public function leaveCommunity(CommunityMember $member): CommunityMember
    {
        return DB::transaction(function () use ($member) {
            $member->status = 'left';
            $member->is_online = false;
            $member->save();
            $member->delete();

            CommunityFollow::where('follower_id', $member->user_id)
                ->where('followable_type', Community::class)
                ->where('followable_id', $member->community_id)
                ->delete();

            Log::info('community.membership.left', [
                'community_id' => $member->community_id,
                'member_id' => $member->getKey(),
            ]);

            return $member;
        });
    }

    public function trackPresence(CommunityMember $member, bool $isOnline, ?CarbonImmutable $lastSeen = null): CommunityMember
    {
        $member->is_online = $isOnline;
        $member->last_seen_at = $lastSeen ?? CarbonImmutable::now();
        $member->save();

        return $member;
    }

    public function bulkApprove(Collection $members): void
    {
        $members->each(function (CommunityMember $member): void {
            if ($member->status !== 'active') {
                $member->status = 'active';
                $member->joined_at = $member->joined_at ?? CarbonImmutable::now();
                $member->save();
            }
        });
    }
}

