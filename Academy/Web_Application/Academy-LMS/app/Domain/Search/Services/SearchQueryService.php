<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Search\Data\SearchQueryData;
use App\Domain\Search\Data\SearchVisibilityContext;
use App\Models\User;
use App\Services\Search\MeilisearchClient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SearchQueryService
{
    public function __construct(
        private readonly MeilisearchClient $client,
        private readonly SearchVisibilityService $visibilityService,
        private readonly SearchVisibilityTokenService $tokenService,
        private readonly array $config = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function execute(SearchQueryData $data): array
    {
        $context = $this->resolveContext($data);

        $payload = $this->compilePayload($data, $context);
        $response = $this->client->search($data->index, $payload);

        $estimatedTotal = (int) ($response['estimatedTotalHits'] ?? 0);
        $hits = Arr::get($response, 'hits', []);
        $appliedFilters = Arr::get($payload, 'filter', []);

        $nextCursor = $this->determineNextCursor($data, $estimatedTotal, count($hits));
        $previousCursor = $this->determinePreviousCursor($data);

        return [
            'index' => $data->index,
            'query' => $data->query,
            'hits' => $hits,
            'estimated_total_hits' => $estimatedTotal,
            'limit' => $data->limit,
            'offset' => $data->offset,
            'applied_filters' => $appliedFilters,
            'sort' => Arr::get($payload, 'sort', []),
            'facets' => Arr::get($response, 'facetDistribution', []),
            'cursor' => [
                'next' => $nextCursor,
                'previous' => $previousCursor,
            ],
        ];
    }

    protected function resolveContext(SearchQueryData $data): SearchVisibilityContext
    {
        if ($data->bypassVisibility) {
            $now = now()->toImmutable();

            return new SearchVisibilityContext(
                userId: $data->user?->getKey(),
                communityIds: [],
                unrestrictedPaidCommunityIds: [],
                subscriptionTierIds: [],
                includePublic: true,
                includeCommunity: true,
                includePaid: true,
                issuedAt: $now,
                expiresAt: $now->addMinutes(15),
            );
        }

        if ($data->visibilityToken) {
            $context = $this->tokenService->validate($data->visibilityToken);

            $userId = $data->user?->getKey();

            if ($context->userId !== null && $userId !== null && $userId !== $context->userId) {
                throw new AuthorizationException('Search visibility token does not belong to the current user.');
            }

            return $context;
        }

        return $this->visibilityService->forUser($data->user instanceof User ? $data->user : null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function compilePayload(SearchQueryData $data, SearchVisibilityContext $context): array
    {
        $filters = $data->bypassVisibility
            ? $this->visibilityFiltersForIndex($data->index)
            : $this->tokenService->compileFilters($context);

        $filters = array_values(array_unique(array_filter(array_merge(
            $filters,
            $data->enforcedFilters,
            $data->filters,
        ))));

        if (empty($filters) && ! $data->bypassVisibility) {
            // Ensure guests always receive public-only results when no filters are generated.
            $filters[] = "visibility = 'public'";
        }

        $payload = [
            'q' => $data->query,
            'limit' => $data->limit,
            'offset' => $data->offset,
        ];

        if (! empty($filters)) {
            $payload['filter'] = $filters;
        }

        if (! empty($data->sort)) {
            $payload['sort'] = $data->sort;
        }

        if (! empty($data->facets)) {
            $payload['facets'] = $data->facets;
        }

        return $payload;
    }

    protected function determineNextCursor(SearchQueryData $data, int $estimatedTotal, int $returnedHits): ?string
    {
        $nextOffset = $data->offset + $returnedHits;

        if ($returnedHits === 0 || $nextOffset >= $estimatedTotal) {
            return null;
        }

        return $this->encodeCursor($data->index, $nextOffset, $data->limit, $data->sort);
    }

    protected function determinePreviousCursor(SearchQueryData $data): ?string
    {
        if ($data->offset <= 0) {
            return null;
        }

        $previousOffset = max(0, $data->offset - $data->limit);

        return $this->encodeCursor($data->index, $previousOffset, $data->limit, $data->sort);
    }

    protected function encodeCursor(string $index, int $offset, int $limit, array $sort): string
    {
        $payload = json_encode([
            'i' => $index,
            'o' => $offset,
            'l' => $limit,
            's' => $sort,
        ], JSON_THROW_ON_ERROR);

        return Str::of($payload)->base64Encode();
    }

    /**
     * @return array<int, string>
     */
    protected function visibilityFiltersForIndex(string $index): array
    {
        $visibilityConfig = Arr::get($this->config, 'visibility.index_filters', []);
        $filter = Arr::get($visibilityConfig, $index);

        return $filter ? [$filter] : [];
    }
}

