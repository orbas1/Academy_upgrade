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
        return $this->ok([
            'community_id' => $community->getKey(),
            'filters' => $request->all(),
        ]);
    }

    public function update(UpdateGeoBoundsRequest $request, Community $community): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'bounds' => $request->validated(),
        ]);
    }

    public function destroy(Community $community, GeoPlace $place): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'place_id' => $place->getKey(),
        ], 204);
    }
}
