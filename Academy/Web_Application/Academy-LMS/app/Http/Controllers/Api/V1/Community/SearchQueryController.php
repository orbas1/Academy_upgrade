<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Domain\Search\Services\SearchQueryService;
use App\Http\Requests\Search\SearchQueryRequest;
use Illuminate\Http\JsonResponse;

class SearchQueryController extends CommunityApiController
{
    public function __construct(private readonly SearchQueryService $searchService)
    {
    }

    public function __invoke(SearchQueryRequest $request): JsonResponse
    {
        $data = $request->toSearchQueryData();
        $results = $this->searchService->execute($data);

        return $this->ok($results);
    }
}

