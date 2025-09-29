<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Domain\Search\Data\SearchQueryParameters;
use App\Domain\Search\Services\SearchQueryService;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use App\Http\Requests\Search\SearchRequest;
use Illuminate\Http\JsonResponse;

class SearchController extends CommunityApiController
{
    public function __construct(
        protected SearchQueryService $queryService,
        protected SearchVisibilityTokenService $tokenService
    ) {
    }

    public function __invoke(SearchRequest $request): JsonResponse
    {
        $context = $this->tokenService->validate((string) $request->string('visibility_token'));

        $parameters = new SearchQueryParameters(
            scope: (string) $request->string('scope'),
            query: (string) $request->input('query', ''),
            filters: $request->input('filters', []),
            sort: $request->input('sort'),
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 20),
        );

        $filters = $this->tokenService->compileFilters($context);

        $result = $this->queryService->search($parameters, $filters, false);

        return $this->ok($result);
    }
}

