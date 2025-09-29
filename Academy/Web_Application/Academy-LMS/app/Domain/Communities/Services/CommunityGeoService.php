<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\GeoPlace;
use Illuminate\Support\Collection;

class CommunityGeoService
{
    public function updateBounds(Community $community, array $polygon): Community
    {
        $settings = $community->settings ?? [];
        $settings['geo_bounds'] = $polygon;
        $community->settings = $settings;
        $community->save();

        return $community;
    }

    public function assignPlace(Community $community, array $place): Community
    {
        $geoPlace = GeoPlace::updateOrCreate(
            [
                'name' => $place['name'],
                'type' => $place['type'] ?? 'custom',
            ],
            [
                'latitude' => $place['latitude'] ?? null,
                'longitude' => $place['longitude'] ?? null,
                'bounding_box' => $place['bounding_box'] ?? null,
                'country_code' => $place['country_code'] ?? null,
                'timezone' => $place['timezone'] ?? null,
                'metadata' => $place['metadata'] ?? [],
            ]
        );

        $community->geoPlace()->associate($geoPlace);
        $community->save();

        return $community;
    }

    public function importGeoJson(Community $community, array $features): Collection
    {
        return collect($features)->map(function ($feature) use ($community) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? [];

            return GeoPlace::create([
                'name' => $properties['name'] ?? $community->name,
                'type' => $properties['type'] ?? 'feature',
                'latitude' => $properties['latitude'] ?? null,
                'longitude' => $properties['longitude'] ?? null,
                'bounding_box' => $geometry['coordinates'] ?? null,
                'country_code' => $properties['country_code'] ?? null,
                'timezone' => $properties['timezone'] ?? null,
                'metadata' => $properties,
            ]);
        });
    }
}

