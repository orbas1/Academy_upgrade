<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Community\UpdateCommunityRequest;
use App\Models\Community\Community;
use App\Services\Community\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends CommunityApiController
{
    public function __construct(private readonly MembershipService $memberships)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->ok([
            'filters' => $request->all(),
        ]);
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        return $this->ok($request->validated(), 201);
    }

    public function show(Community $community): JsonResponse
    {
        return $this->ok([
            'community' => $community->toArray(),
        ]);
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        return $this->ok([
            'community' => array_merge($community->toArray(), $request->validated()),
        ]);
    }

    public function destroy(Community $community): JsonResponse
    {
        return $this->ok([], 204);
    }
}
