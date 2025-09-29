<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Search\Models\SearchSavedQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'search.meilisearch.host' => 'https://search.test',
            'search.visibility.token_secret' => 'admin-secret',
        ]);
    }

    public function test_admin_audit_applies_flag_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        Http::fake([
            'https://search.test/indexes/posts/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ]),
        ]);

        $response = $this->postJson('/api/v1/admin/search/audit', [
            'index' => 'posts',
            'flags' => ['flagged'],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return in_array("moderation.state = 'flagged'", $payload['filter'] ?? [], true);
        });
    }

    public function test_saved_search_crud_flow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $store = $this->postJson('/api/v1/admin/search/saved-queries', [
            'name' => 'Flagged posts',
            'index' => 'posts',
            'flags' => ['flagged', 'reported'],
            'is_shared' => true,
        ]);

        $store->assertCreated();

        $queryId = $store->json('data.id');

        $list = $this->getJson('/api/v1/admin/search/saved-queries');
        $list->assertOk();
        $list->assertJsonFragment(['name' => 'Flagged posts']);

        $this->deleteJson('/api/v1/admin/search/saved-queries/' . $queryId)
            ->assertNoContent();

        $this->assertDatabaseMissing('search_saved_queries', [
            'id' => $queryId,
        ]);
    }

    public function test_saved_search_usage_updates_timestamp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $saved = SearchSavedQuery::query()->create([
            'user_id' => $admin->getKey(),
            'name' => 'Spam Sweep',
            'index' => 'posts',
            'flags' => ['spam'],
            'filters' => ['community_id = 42'],
            'is_shared' => false,
        ]);

        Http::fake([
            'https://search.test/indexes/posts/search' => Http::response([
                'hits' => [],
                'estimatedTotalHits' => 0,
            ]),
        ]);

        $this->postJson('/api/v1/admin/search/audit', [
            'saved_search_id' => $saved->getKey(),
        ])->assertOk();

        $this->assertNotNull($saved->fresh()->last_used_at);
    }
}

