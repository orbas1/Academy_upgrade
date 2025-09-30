<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use App\Http\Requests\Community\ManageMemberRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Services\Community\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CommunityMemberController extends CommunityApiController
{
    public function __construct(private readonly MembershipService $memberships)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'role' => $request->query('role'),
            'online' => $request->query('online'),
            'search' => $request->query('search'),
            'joined_after' => $request->query('joined_after'),
            'joined_before' => $request->query('joined_before'),
            'per_page' => $request->integer('per_page', 25),
            'page' => $request->integer('page', 1),
        ];

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->memberships->getActiveMembers($community, $filters);
        $paginator->setCollection($paginator->getCollection()->map(fn (CommunityMember $member) => $this->formatMember($member)));

        return $this->respondWithPagination($paginator, [
            'community_id' => $community->getKey(),
            'filters' => Arr::only($filters, ['status', 'role', 'online', 'search', 'joined_after', 'joined_before']),
        ]);
    }

    public function update(ManageMemberRequest $request, Community $community, CommunityMember $member): JsonResponse
    {
        $actor = $request->user();

        if (! $actor) {
            return $this->respondWithError('Unauthenticated', 'A user must be authenticated to manage members.', 401);
        }

        $payload = $request->validated();
        $member = $member->fresh(['user']);

        if (isset($payload['role'])) {
            $role = CommunityMemberRole::from($payload['role']);

            $member = $role === CommunityMemberRole::MEMBER
                ? $this->memberships->demoteMember($member, $actor)
                : $this->memberships->promoteMember($member, $role, $actor);
        }

        if (isset($payload['status'])) {
            $status = CommunityMemberStatus::from($payload['status']);
            $message = $payload['message'] ?? null;

            $member = $member->fresh(['user']);

            switch ($status) {
                case CommunityMemberStatus::ACTIVE:
                    if ($member->status === CommunityMemberStatus::BANNED->value) {
                        $member = $this->memberships->unbanMember($member, $actor);
                    } elseif ($member->status !== CommunityMemberStatus::ACTIVE->value) {
                        $member = $this->memberships->approveMember($member, $actor);
                    }
                    break;
                case CommunityMemberStatus::BANNED:
                    $this->memberships->banMember($member, $actor, $message);
                    $member = $member->fresh(['user']);
                    break;
                case CommunityMemberStatus::LEFT:
                    $this->memberships->leaveCommunity($member);
                    $member = CommunityMember::withTrashed()->find($member->getKey());
                    break;
                case CommunityMemberStatus::PENDING:
                    return $this->respondWithError('Unsupported status update', 'Members cannot be reverted to pending through this endpoint.', 422);
            }
        }

        $member = $member?->fresh(['user']);

        return $this->ok([
            'community_id' => $community->getKey(),
            'member' => $member ? $this->formatMember($member) : null,
        ]);
    }

    private function formatMember(CommunityMember $member): array
    {
        $user = $member->user;

        return [
            'id' => (int) $member->getKey(),
            'user_id' => (int) $member->user_id,
            'name' => $user?->name,
            'email' => $user?->email,
            'avatar_url' => $user?->profile_photo_url,
            'role' => $member->role,
            'status' => $member->status,
            'points' => (int) $member->points,
            'joined_at' => optional($member->joined_at)->toIso8601String(),
            'last_seen_at' => optional($member->last_seen_at)->toIso8601String(),
            'is_online' => (bool) $member->is_online,
            'metadata' => $member->metadata ?? [],
        ];
    }
}
