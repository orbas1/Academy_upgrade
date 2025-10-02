<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Manages map bounds, places, and privacy toggles for Orbas Learn communities.
 */
interface GeoService
{
    /**
     * @param  array{north:float,south:float,east:float,west:float,updated_by:int}  $payload
     * @return array{community_id:int,north:float,south:float,east:float,west:float,updated_at:Carbon}
     */
    public function updateBounds(int $communityId, array $payload): array;

    /**
     * @param  array{
     *     name:string,
     *     latitude:float,
     *     longitude:float,
     *     visibility:string,
     *     metadata?:array
     * }  $payload
     * @return array{place_id:int,community_id:int,created_at:Carbon}
     */
    public function createPlace(int $communityId, array $payload): array;

    /**
     * @param  array{name?:string,latitude?:float,longitude?:float,visibility?:string,metadata?:array}  $payload
     * @return array{place_id:int,community_id:int,updated_at:Carbon}
     */
    public function updatePlace(int $placeId, array $payload): array;

    /**
     * Delete the specified place.
     */
    public function deletePlace(int $placeId, int $actorId): void;

    /**
     * Toggle map visibility for community members.
     */
    public function togglePrivacy(int $communityId, bool $isPrivate, int $actorId): void;

    /**
     * List places within the community.
     *
     * @return Collection<int, array>
     */
    public function listPlaces(int $communityId): Collection;
}
