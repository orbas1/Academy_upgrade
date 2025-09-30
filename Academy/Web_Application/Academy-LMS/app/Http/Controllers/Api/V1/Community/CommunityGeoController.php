<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\UpdateGeoBoundsRequest;
use App\Models\Community\Community;
use App\Models\Community\GeoPlace;
use App\Services\Community\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityGeoController extends CommunityApiController
{
    public function __construct(private readonly GeoService $geo)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $paginator = $this->geo->listPlaces($community, $perPage);

        return $this->respondWithPagination($paginator, [
            'community_id' => $community->getKey(),
        ]);
    }

    public function update(UpdateGeoBoundsRequest $request, Community $community): JsonResponse
    {
        $polygon = $request->validated()['polygon'];
        $privacy = $request->validated()['privacy'] ?? null;

        $community = $this->geo->updateBounds($community, $polygon, $privacy);

        return $this->ok([
            'community_id' => $community->getKey(),
            'settings' => $community->settings,
        ]);
    }

    public function destroy(Community $community, GeoPlace $place): JsonResponse
    {
        $this->geo->removePlace($community, $place);

        return $this->respondNoContent();
    }
}
