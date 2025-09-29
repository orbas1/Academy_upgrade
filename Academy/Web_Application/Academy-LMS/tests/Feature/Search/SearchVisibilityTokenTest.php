<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchVisibilityTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'search.visibility.token_secret' => 'feature-secret',
            'search.visibility.ttl' => 300,
        ]);
    }

    public function test_guest_can_request_public_visibility_token(): void
    {
        $response = $this->getJson('/api/v1/search/visibility-token');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['token', 'filters', 'issued_at', 'expires_at'],
        ]);

        $filters = $response->json('data.filters');
        $this->assertContains("visibility = 'public'", $filters);
    }

    public function test_authenticated_user_receives_member_visibility(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $community = Community::query()->create([
            'slug' => Str::slug('Delta ' . Str::random(6)),
            'name' => 'Delta',
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        CommunityMember::query()->create([
            'community_id' => $community->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/search/visibility-token');

        $response->assertOk();
        $filters = $response->json('data.filters');

        $this->assertNotEmpty(
            array_filter(
                $filters,
                fn ($filter) => str_contains((string) $filter, (string) $community->getKey())
            ),
            'Expected community visibility filter to include the member community.'
        );
    }
}

