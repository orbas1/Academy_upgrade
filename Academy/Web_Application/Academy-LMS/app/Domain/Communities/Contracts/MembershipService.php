<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;

/**
 * Defines membership lifecycle responsibilities for Orbas Learn communities.
 */
interface MembershipService
{
    /**
     * Request to join a community and return the resulting membership snapshot.
     *
     * @param  array{source?: string, message?: string}  $context
     * @return array{
     *     community_id: int,
     *     user_id: int,
     *     status: string,
     *     role: string,
     *     requested_at: Carbon,
     *     last_seen_at: Carbon|null,
     *     is_online: bool
     * }
     */
    public function join(int $communityId, int $userId, array $context = []): array;

    /**
     * Approve a pending member.
     *
     * @return array{community_id:int,user_id:int,status:string,role:string,approved_by:int,approved_at:Carbon}
     */
    public function approve(int $communityId, int $userId, int $actorId): array;

    /**
     * Allow a member to voluntarily leave the community.
     */
    public function leave(int $communityId, int $userId): void;

    /**
     * Ban a member and capture ban metadata.
     *
     * @param  array{actor_id:int,reason?:string,expires_at?:Carbon}  $context
     * @return array{community_id:int,user_id:int,status:string,role:string,ban_reason:string,ban_expires_at:Carbon|null}
     */
    public function ban(int $communityId, int $userId, array $context): array;

    /**
     * Promote or demote a member to the provided role.
     *
     * @return array{community_id:int,user_id:int,role:string,changed_by:int,changed_at:Carbon}
     */
    public function promote(int $communityId, int $userId, string $role, int $actorId): array;

    /**
     * Return a computed capability matrix for the provided user.
     *
     * @return array{
     *     is_owner: bool,
     *     is_admin: bool,
     *     is_moderator: bool,
     *     can_post: bool,
     *     can_comment: bool,
     *     can_manage_members: bool,
     *     can_manage_paywall: bool
     * }
     */
    public function computeRoleChecks(int $communityId, int $userId): array;

    /**
     * Touch the member's last-seen marker and update presence if applicable.
     */
    public function touchLastSeen(int $communityId, int $userId, ?Carbon $at = null): void;

    /**
     * Persist an explicit online heartbeat for presence channels.
     */
    public function heartbeat(int $communityId, int $userId, ?Carbon $at = null): void;
}
