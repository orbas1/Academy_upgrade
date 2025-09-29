<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Domain\Search\Data\SearchVisibilityContext;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use App\Http\Controllers\Api\V1\Community\SearchController;
use App\Http\Requests\Search\SearchRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Support\Concerns\UsesInMemoryDatabase;

class SearchQueryControllerTest extends TestCase
{
    use UsesInMemoryDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('search.visibility.token_secret', 'testing-secret');
        config()->set('search.scopes', [
            'posts' => [
                'index' => 'posts',
                'allowed_filters' => ['community_id', 'visibility'],
                'allowed_sorts' => ['created_at'],
                'default_sort' => 'created_at:desc',
                'facets' => ['visibility'],
            ],
        ]);

        $this->useInMemoryDatabase();
    }

    public function test_search_requires_visibility_token(): void
    {
        $request = SearchRequest::create('/api/v1/search', 'POST', [
            'scope' => 'posts',
            'query' => 'hello',
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $this->expectException(ValidationException::class);
        $request->validateResolved();
    }

    public function test_search_applies_visibility_filters(): void
    {
        Http::fake([
            'http://meilisearch:7700/indexes/posts/search' => Http::response([
                'hits' => [
                    [
                        'id' => 10,
                        'title' => 'Hello world',
                        '_formatted' => [],
                    ],
                ],
                'estimatedTotalHits' => 1,
            ], 200),
        ]);

        $context = new SearchVisibilityContext(
            userId: 1,
            communityIds: [42],
            unrestrictedPaidCommunityIds: [42],
            subscriptionTierIds: [],
            includePublic: true,
            includeCommunity: true,
            includePaid: false,
            issuedAt: now()->toImmutable(),
            expiresAt: now()->addMinutes(15)->toImmutable()
        );

        $token = app(SearchVisibilityTokenService::class)->issue($context);

        $request = SearchRequest::create('/api/v1/search', 'POST', [
            'scope' => 'posts',
            'query' => 'hello',
            'visibility_token' => $token['token'],
        ]);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->validateResolved();

        $controller = app(SearchController::class);
        $response = $controller($request);

        $payload = $response->getData(true);

        $this->assertSame(10, $payload['data']['hits'][0]['id']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            $this->assertArrayHasKey('filter', $payload);
            $this->assertContains("visibility = 'public'", $payload['filter']);
            $this->assertContains("(visibility = 'community' AND community_id IN [42])", $payload['filter']);

            return true;
        });
    }
}

