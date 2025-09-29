<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Domain\Search\Services\SearchQueryService;
use App\Http\Requests\Search\SearchQueryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class SearchQueryController extends CommunityApiController
{
    public function __construct(private readonly SearchQueryService $searchService)
    {
    }

    public function __invoke(SearchQueryRequest $request): JsonResponse
    {
        $data = $request->toSearchQueryData();
        $results = $this->searchService->execute($data);

        $payload = [
            'index' => Arr::get($results, 'index', ''),
            'query' => Arr::get($results, 'query', ''),
            'hits' => Arr::get($results, 'hits', []),
            'facets' => Arr::get($results, 'facets', []),
        ];

        $meta = [
            'applied_filters' => Arr::get($results, 'applied_filters', []),
            'sort' => Arr::get($results, 'sort', []),
            'estimated_total_hits' => (int) Arr::get($results, 'estimated_total_hits', 0),
            'pagination' => [
                'type' => 'cursor',
                'limit' => (int) Arr::get($results, 'limit', 0),
                'offset' => (int) Arr::get($results, 'offset', 0),
                'count' => count(Arr::get($results, 'hits', [])),
                'estimated_total' => (int) Arr::get($results, 'estimated_total_hits', 0),
                'next_cursor' => Arr::get($results, 'cursor.next'),
                'previous_cursor' => Arr::get($results, 'cursor.previous'),
                'has_more' => Arr::get($results, 'cursor.next') !== null,
            ],
        ];

        return $this->respondWithData($payload, $meta);
    }
}

