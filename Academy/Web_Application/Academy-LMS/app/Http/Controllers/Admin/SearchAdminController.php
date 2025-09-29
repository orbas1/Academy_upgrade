<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Search\Data\SearchQueryParameters;
use App\Domain\Search\Models\AdminSavedSearch;
use App\Domain\Search\Models\SearchAuditLog;
use App\Domain\Search\Services\AdminSearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Search\RunAdminSearchRequest;
use App\Http\Requests\Admin\Search\StoreSavedSearchRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class SearchAdminController extends Controller
{
    public function __construct(
        protected AdminSearchService $adminSearchService
    ) {
    }

    public function index(): View
    {
        return view('admin.search.index', [
            'savedSearches' => AdminSavedSearch::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(),
            'logs' => SearchAuditLog::query()
                ->latest('executed_at')
                ->limit(50)
                ->get(),
            'scopes' => array_keys((array) config('search.scopes', [])),
            'activeResult' => null,
        ]);
    }

    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        $filters = $this->normaliseFilters($request->input('filters'));

        AdminSavedSearch::updateOrCreate(
            [
                'user_id' => $request->user()->getKey(),
                'name' => $request->string('name'),
            ],
            [
                'scope' => $request->string('scope'),
                'query' => $request->input('query'),
                'filters' => $filters,
                'sort' => $request->input('sort'),
                'frequency' => $request->string('frequency'),
            ]
        );

        return redirect()
            ->route('admin.search.index')
            ->with('success', 'Saved search updated successfully.');
    }

    public function destroy(AdminSavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === auth()->id(), 403);

        $savedSearch->delete();

        return redirect()
            ->route('admin.search.index')
            ->with('success', 'Saved search removed.');
    }

    public function run(RunAdminSearchRequest $request): View|JsonResponse
    {
        $savedSearch = null;

        if ($request->filled('saved_search_id')) {
            $savedSearch = AdminSavedSearch::query()
                ->where('user_id', $request->user()->getKey())
                ->findOrFail($request->integer('saved_search_id'));
        }

        $parameters = $this->buildParameters($request, $savedSearch);

        $result = $this->adminSearchService->runSearch($parameters, $request->user());

        if ($savedSearch) {
            $this->adminSearchService->markTriggered($savedSearch);
        }

        $payload = [
            'savedSearches' => AdminSavedSearch::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(),
            'logs' => SearchAuditLog::query()
                ->latest('executed_at')
                ->limit(50)
                ->get(),
            'scopes' => array_keys((array) config('search.scopes', [])),
            'activeResult' => [
                'meta' => [
                    'scope' => $parameters->scope,
                    'query' => $parameters->query,
                    'filters' => $parameters->filters,
                ],
                'data' => $result,
                'label' => $savedSearch?->name ?? 'Ad hoc search',
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return view('admin.search.index', $payload);
    }

    protected function buildParameters(
        RunAdminSearchRequest $request,
        ?AdminSavedSearch $savedSearch = null
    ): SearchQueryParameters {
        if ($savedSearch) {
            return new SearchQueryParameters(
                scope: $savedSearch->scope,
                query: $savedSearch->query ?? '',
                filters: $savedSearch->filters ?? [],
                sort: $savedSearch->sort,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('per_page', 20),
            );
        }

        $filters = $this->normaliseFilters($request->input('filters'));

        return new SearchQueryParameters(
            scope: $request->string('scope'),
            query: (string) $request->input('query', ''),
            filters: $filters,
            sort: $request->input('sort'),
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 20),
        );
    }

    protected function normaliseFilters(mixed $filters): array
    {
        if (is_string($filters) && $filters !== '') {
            $decoded = json_decode($filters, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($filters)) {
            return Arr::where($filters, fn ($value) => $value !== null && $value !== '');
        }

        return [];
    }
}

