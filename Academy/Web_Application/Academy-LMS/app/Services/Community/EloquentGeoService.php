<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Models\GeoPlace as DomainGeoPlace;
use App\Domain\Communities\Services\CommunityGeoService;
use App\Models\Community\Community;
use App\Models\Community\GeoPlace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class EloquentGeoService implements GeoService
{
    public function __construct(private readonly CommunityGeoService $geoService)
    {
    }

    public function updateBounds(Community $community, array $polygon, ?array $privacy = null): Community
    {
        $normalized = $this->normalizePolygon($polygon);
        $community = $this->geoService->updateBounds($community, $normalized);

        if ($privacy !== null) {
            $settings = $community->settings ?? [];
            $settings['geo_privacy'] = [
                'visible' => (bool) ($privacy['visible'] ?? true),
                'updated_at' => now()->toIso8601String(),
            ];
            $community->settings = $settings;
            $community->save();
        }

        return $community->fresh();
    }

    public function addPlace(Community $community, array $payload): GeoPlace
    {
        $placePayload = array_merge($payload, [
            'metadata' => array_merge($payload['metadata'] ?? [], [
                'community_id' => $community->getKey(),
            ]),
        ]);

        $updatedCommunity = $this->geoService->assignPlace($community, $placePayload);

        return $updatedCommunity->geoPlace->refresh();
    }

    public function removePlace(Community $community, GeoPlace $place): void
    {
        if ($community->geo_place_id === $place->getKey()) {
            $community->geoPlace()->dissociate();
            $community->save();
        }

        if (($place->metadata['community_id'] ?? null) === $community->getKey()) {
            $place->delete();
        }
    }

    public function importGeoJson(Community $community, string $path): Collection
    {
        if (! Storage::disk()->exists($path)) {
            throw new RuntimeException("GeoJSON file {$path} does not exist");
        }

        $contents = Storage::disk()->get($path);
        $decoded = json_decode($contents, true);

        if (! is_array($decoded) || ! isset($decoded['features']) || ! is_array($decoded['features'])) {
            throw new InvalidArgumentException('GeoJSON content must include a features array.');
        }

        $features = array_map(function (array $feature) use ($community) {
            $properties = $feature['properties'] ?? [];
            $properties['community_id'] = $community->getKey();
            $feature['properties'] = $properties;

            return $feature;
        }, $decoded['features']);

        return $this->geoService->importGeoJson($community, $features)
            ->tap(function (Collection $collection) use ($community) {
                $collection->each(function (DomainGeoPlace $place) use ($community): void {
                    $place->metadata = array_merge($place->metadata ?? [], [
                        'community_id' => $community->getKey(),
                        'imported_at' => now()->toIso8601String(),
                    ]);
                    $place->save();
                });
            });
    }

    public function listPlaces(Community $community, int $perPage = 50): LengthAwarePaginatorContract
    {
        $perPage = max(5, min($perPage, 200));
        $page = Paginator::resolveCurrentPage();

        $query = GeoPlace::query()
            ->where(function ($builder) use ($community) {
                $builder->where('id', $community->geo_place_id);
                $builder->orWhere('metadata->community_id', $community->getKey());
            })
            ->orderByDesc('updated_at');

        $total = (clone $query)->count();

        $items = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (GeoPlace $place) => $this->formatPlace($place));

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    private function normalizePolygon(array $polygon): array
    {
        if (! isset($polygon[0]) || ! is_array($polygon[0])) {
            throw new InvalidArgumentException('Polygon coordinates must be a nested array.');
        }

        return array_map(function ($coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2) {
                throw new InvalidArgumentException('Each polygon coordinate must include latitude and longitude.');
            }

            return [
                (float) $coordinate[0],
                (float) $coordinate[1],
            ];
        }, $polygon);
    }

    private function formatPlace(GeoPlace $place): array
    {
        return [
            'id' => (int) $place->getKey(),
            'name' => $place->name,
            'type' => $place->type,
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
            'country_code' => $place->country_code,
            'timezone' => $place->timezone,
            'bounding_box' => $place->bounding_box,
            'metadata' => $place->metadata,
            'created_at' => optional($place->created_at)->toIso8601String(),
            'updated_at' => optional($place->updated_at)->toIso8601String(),
        ];
    }
}
