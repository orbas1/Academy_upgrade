<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Search\Services\SearchVisibilityService;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchQueryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'search.meilisearch.host' => 'https://search.test',
            'search.visibility.token_secret' => 'testing-secret',
        ]);
    }

    public function test_guest_searches_are_limited_to_public_visibility(): void
    {
        Http::fake([
            'https://search.test/indexes/posts/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ]),
        ]);

        $response = $this->postJson('/api/v1/search/query', [
            'index' => 'posts',
            'query' => 'laravel',
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return in_array("visibility = 'public'", $payload['filter'] ?? [], true);
        });
    }

    public function test_mismatched_visibility_token_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $other = User::factory()->create(['role' => 'member']);

        Sanctum::actingAs($user);

        $visibilityService = app(SearchVisibilityService::class);
        $tokenService = app(SearchVisibilityTokenService::class);

        $context = $visibilityService->forUser($other);
        $token = $tokenService->issue($context);

        Http::fake([
            'https://search.test/indexes/posts/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ]),
        ]);

        $response = $this->postJson('/api/v1/search/query', [
            'index' => 'posts',
            'query' => 'laravel',
            'visibility_token' => $token['token'],
        ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_filters_include_membership_and_token_filters(): void
    {
        $user = User::factory()->create(['role' => 'moderator']);
        Sanctum::actingAs($user);

        $visibilityService = app(SearchVisibilityService::class);
        $tokenService = app(SearchVisibilityTokenService::class);

        $context = $visibilityService->forUser($user);
        $token = $tokenService->issue($context);

        Http::fake([
            'https://search.test/indexes/posts/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ]),
        ]);

        $response = $this->postJson('/api/v1/search/query', [
            'index' => 'posts',
            'query' => 'points',
            'visibility_token' => $token['token'],
            'filters' => ['community_id = 5'],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return in_array('community_id = 5', $payload['filter'] ?? [], true);
        });
    }
}

