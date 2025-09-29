<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\ManageSubscriptionRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunitySubscription;
use App\Models\Community\CommunitySubscriptionTier;
use App\Services\Community\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunitySubscriptionController extends CommunityApiController
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'filters' => $request->all(),
        ]);
    }

    public function store(ManageSubscriptionRequest $request, Community $community, CommunityMember $member, CommunitySubscriptionTier $tier): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'member_id' => $member->getKey(),
            'tier_id' => $tier->getKey(),
            'payload' => $request->validated(),
        ], 201);
    }

    public function destroy(Community $community, CommunitySubscription $subscription): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'subscription_id' => $subscription->getKey(),
        ], 204);
    }
}
