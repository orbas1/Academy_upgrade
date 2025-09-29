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

        return $this->ok($results);
    }
}

