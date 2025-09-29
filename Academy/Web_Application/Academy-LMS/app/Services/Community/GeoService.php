<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\GeoPlace;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Contract for geo-boundary and place management.
 */
interface GeoService
{
    public function updateBounds(Community $community, array $polygon, ?array $privacy = null): Community;

    public function addPlace(Community $community, array $payload): GeoPlace;

    public function removePlace(Community $community, GeoPlace $place): void;

    public function importGeoJson(Community $community, string $path): Collection;

    public function listPlaces(Community $community, int $perPage = 50): Paginator;
}
