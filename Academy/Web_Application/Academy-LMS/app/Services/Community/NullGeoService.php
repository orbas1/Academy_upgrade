<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullGeoService implements GeoService
{
    use NotImplemented;
    public function updateBounds(\App\Models\Community\Community $community, array $polygon, ?array $privacy = null): \App\Models\Community\Community
    {
        $this->notImplemented();
    }

    public function addPlace(\App\Models\Community\Community $community, array $payload): \App\Models\Community\GeoPlace
    {
        $this->notImplemented();
    }

    public function removePlace(\App\Models\Community\Community $community, \App\Models\Community\GeoPlace $place): void
    {
        $this->notImplemented();
    }

    public function importGeoJson(\App\Models\Community\Community $community, string $path): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function listPlaces(\App\Models\Community\Community $community, int $perPage = 50): \Illuminate\Contracts\Pagination\Paginator
    {
        $this->notImplemented();
    }
}
