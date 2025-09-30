<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\GeoPlace;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityGeoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 1, 21, 12));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_member_can_list_geo_places(): void
    {
        $community = Community::factory()->create();
        $user = User::find($community->created_by);
        Sanctum::actingAs($user);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $place = GeoPlace::create([
            'name' => 'Downtown',
            'type' => 'neighborhood',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'metadata' => ['community_id' => $community->getKey()],
        ]);

        $response = $this->getJson("/api/v1/communities/{$community->getKey()}/geo/places");

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Downtown']);
    }

    public function test_owner_can_update_geo_bounds(): void
    {
        $community = Community::factory()->create();
        $user = User::find($community->created_by);
        Sanctum::actingAs($user);

        CommunityMember::factory()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $payload = [
            'polygon' => [[40.0, -73.0], [41.0, -73.5], [40.5, -74.0]],
            'privacy' => ['visible' => true],
        ];

        $response = $this->putJson("/api/v1/communities/{$community->getKey()}/geo/bounds", $payload);

        $response->assertOk();
        $response->assertJsonFragment(['community_id' => $community->getKey()]);
        $this->assertEquals($payload['polygon'], $community->fresh()->settings['geo_bounds']);
    }
}
