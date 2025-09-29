<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\ManageMemberRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Services\Community\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityMemberController extends CommunityApiController
{
    public function __construct(private readonly MembershipService $memberships)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'filters' => $request->all(),
        ]);
    }

    public function update(ManageMemberRequest $request, Community $community, CommunityMember $member): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'member_id' => $member->getKey(),
            'payload' => $request->validated(),
        ]);
    }
}
