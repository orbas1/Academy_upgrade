<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\UpdateNotificationPreferencesRequest;
use App\Models\Community\Community;
use App\Services\Messaging\NotificationPreferenceService;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityNotificationPreferenceController extends CommunityApiController
{
    public function __construct(
        private readonly NotificationPreferenceService $service,
        private readonly NotificationPreferenceResolver $resolver
    ) {
    }

    public function show(Request $request, Community $community): JsonResponse
    {
        $user = $request->user();
        $preference = $this->resolver->for($user, $community->getKey());

        return $this->ok([
            'community_id' => $community->getKey(),
            'channel_email' => $preference->channel_email,
            'channel_push' => $preference->channel_push,
            'channel_in_app' => $preference->channel_in_app,
            'digest_frequency' => $preference->digest_frequency,
            'muted_events' => $preference->muted_events,
            'locale' => $preference->preferredLocale(),
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request, Community $community): JsonResponse
    {
        $user = $request->user();
        $preference = $this->service->update($user, $community->getKey(), $request->validated());

        return $this->ok([
            'community_id' => $community->getKey(),
            'channel_email' => $preference->channel_email,
            'channel_push' => $preference->channel_push,
            'channel_in_app' => $preference->channel_in_app,
            'digest_frequency' => $preference->digest_frequency,
            'muted_events' => $preference->muted_events,
            'locale' => $preference->preferredLocale(),
        ]);
    }

    public function destroy(Request $request, Community $community): JsonResponse
    {
        $user = $request->user();
        $this->service->delete($user, $community->getKey());

        return response()->json([], 204);
    }
}
