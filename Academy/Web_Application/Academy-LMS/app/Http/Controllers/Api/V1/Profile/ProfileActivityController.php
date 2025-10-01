<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Domain\Communities\Models\ProfileActivity;
use App\Http\Controllers\Api\V1\Community\CommunityApiController;
use App\Http\Requests\Profile\ProfileActivityIndexRequest;
use Illuminate\Http\JsonResponse;

class ProfileActivityController extends CommunityApiController
{
    public function index(ProfileActivityIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) min(max($request->integer('per_page', 25), 1), 100);
        $communityId = $request->integer('community_id');

        $query = ProfileActivity::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($communityId) {
            $query->where('community_id', $communityId);
        }

        $paginator = $query->cursorPaginate($perPage);
        $paginator->setCollection(
            $paginator->getCollection()->map(function (ProfileActivity $activity): array {
                $activity->loadMissing('community:id,name,slug');

                return [
                    'id' => (int) $activity->getKey(),
                    'activity_type' => $activity->activity_type,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => (int) $activity->subject_id,
                    'occurred_at' => optional($activity->occurred_at)->toIso8601String(),
                    'community' => $activity->community ? [
                        'id' => (int) $activity->community->getKey(),
                        'name' => $activity->community->name,
                        'slug' => $activity->community->slug,
                    ] : null,
                    'context' => $activity->context ?? [],
                ];
            })
        );

        return $this->respondWithPagination($paginator, [
            'filters' => array_filter([
                'community_id' => $communityId,
            ]),
        ]);
    }
}
