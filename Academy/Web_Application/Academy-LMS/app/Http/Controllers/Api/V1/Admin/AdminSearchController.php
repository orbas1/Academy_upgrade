<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Search\Models\SearchSavedQuery;
use App\Domain\Search\Services\SearchQueryService;
use App\Http\Controllers\Api\V1\Community\CommunityApiController;
use App\Http\Requests\Search\AdminSearchRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;

class AdminSearchController extends CommunityApiController
{
    public function __construct(private readonly SearchQueryService $searchService)
    {
    }

    public function audit(AdminSearchRequest $request): JsonResponse
    {
        Gate::authorize('search.audit');

        /** @var User $actor */
        $actor = $request->user();

        $savedQuery = null;

        if ($request->filled('saved_search_id')) {
            $savedQuery = SearchSavedQuery::query()->findOrFail((int) $request->input('saved_search_id'));

            if (! $savedQuery->isOwnedBy($actor) && ! $actor->can('search.saved')) {
                abort(403, 'You are not allowed to use this saved query.');
            }
        }

        $data = $request->toSearchQueryData($savedQuery);
        $results = $this->searchService->execute($data);

        if ($savedQuery !== null) {
            $savedQuery->markUsed();
        }

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

