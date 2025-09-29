<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Search\Models\SearchSavedQuery;
use App\Http\Controllers\Api\V1\Community\CommunityApiController;
use App\Http\Requests\Search\StoreSavedSearchRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminSavedSearchController extends CommunityApiController
{
    public function index(): JsonResponse
    {
        Gate::authorize('search.saved');

        /** @var User $user */
        $user = request()->user();

        $queries = SearchSavedQuery::query()
            ->accessibleTo($user)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->map(fn (SearchSavedQuery $query) => [
                'id' => $query->getKey(),
                'name' => $query->name,
                'index' => $query->index,
                'query' => $query->query,
                'filters' => $query->filters ?? [],
                'flags' => $query->flags ?? [],
                'sort' => $query->sort ?? [],
                'is_shared' => $query->is_shared,
                'last_used_at' => optional($query->last_used_at)->toIso8601String(),
                'created_at' => optional($query->created_at)->toIso8601String(),
                'updated_at' => optional($query->updated_at)->toIso8601String(),
            ]);

        return $this->ok(['items' => $queries]);
    }

    public function store(StoreSavedSearchRequest $request): JsonResponse
    {
        Gate::authorize('search.saved');

        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $query = SearchSavedQuery::query()->create([
            'user_id' => $user->getKey(),
            'name' => $validated['name'],
            'index' => $validated['index'],
            'query' => $validated['query'] ?? null,
            'filters' => $validated['filters'] ?? [],
            'flags' => $validated['flags'] ?? [],
            'sort' => $validated['sort'] ?? [],
            'is_shared' => (bool) ($validated['is_shared'] ?? false),
        ]);

        return $this->ok([
            'id' => $query->getKey(),
        ], 201);
    }

    public function destroy(SearchSavedQuery $savedQuery): JsonResponse
    {
        Gate::authorize('search.saved');

        /** @var User $user */
        $user = request()->user();

        if (! $savedQuery->isOwnedBy($user) && ! $user->can('search.audit')) {
            abort(403, 'You are not allowed to delete this saved query.');
        }

        $savedQuery->delete();

        return response()->json([], 204);
    }
}

