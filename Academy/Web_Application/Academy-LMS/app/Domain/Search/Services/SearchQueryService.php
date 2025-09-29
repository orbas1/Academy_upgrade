<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Search\Data\SearchQueryParameters;
use App\Services\Search\MeilisearchClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use function collect;

class SearchQueryService
{
    /**
     * @param array<string, array<string, mixed>> $scopeConfig
     */
    public function __construct(
        protected MeilisearchClient $client,
        protected array $scopeConfig = []
    ) {
        $this->scopeConfig = $scopeConfig ?: (array) config('search.scopes', []);
    }

    /**
     * @param array<int, string> $visibilityFilters
     * @return array<string, mixed>
     */
    public function search(
        SearchQueryParameters $parameters,
        array $visibilityFilters = [],
        bool $adminMode = false
    ): array {
        if ($parameters->scope === 'all') {
            return $this->searchAllScopes($parameters, $visibilityFilters, $adminMode);
        }

        return $this->searchScope($parameters->scope, $parameters, $visibilityFilters, $adminMode);
    }

    /**
     * @param array<int, string> $visibilityFilters
     * @return array<string, mixed>
     */
    protected function searchAllScopes(
        SearchQueryParameters $parameters,
        array $visibilityFilters,
        bool $adminMode
    ): array {
        $results = [];
        $meta = [
            'query' => $parameters->query,
            'scope' => 'all',
            'page' => $parameters->page,
            'per_page' => $parameters->perPage,
        ];

        foreach (array_keys($this->scopeConfig) as $scope) {
            $scopedParameters = new SearchQueryParameters(
                $scope,
                $parameters->query,
                $parameters->filters,
                $parameters->sort,
                1,
                min($parameters->perPage, 10)
            );

            $results[$scope] = $this->searchScope($scope, $scopedParameters, $visibilityFilters, $adminMode);
        }

        return [
            'data' => $results,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<int, string> $visibilityFilters
     * @return array<string, mixed>
     */
    protected function searchScope(
        string $scope,
        SearchQueryParameters $parameters,
        array $visibilityFilters,
        bool $adminMode
    ): array {
        $config = $this->scopeConfig[$scope] ?? null;

        if (! $config) {
            throw new InvalidArgumentException(sprintf('Unknown search scope [%s]', $scope));
        }

        $filters = $this->compileFilters($config, $parameters->filters, $visibilityFilters, $adminMode);
        $payload = $this->buildSearchPayload($scope, $config, $parameters, $filters);

        $response = $this->client->search($config['index'], $payload);

        return [
            'hits' => $this->formatHits($response['hits'] ?? [], $scope),
            'total' => (int) ($response['estimatedTotalHits'] ?? 0),
            'page' => $parameters->page,
            'per_page' => $parameters->perPage,
            'facets' => $response['facetDistribution'] ?? [],
            'processing_time_ms' => $response['processingTimeMs'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $filters
     * @param array<int, string> $visibilityFilters
     * @return array<int, string>
     */
    protected function compileFilters(
        array $config,
        array $filters,
        array $visibilityFilters,
        bool $adminMode
    ): array {
        $expressions = collect($visibilityFilters);

        $allowed = collect($config['allowed_filters'] ?? []);

        if ($adminMode) {
            $allowed = $allowed->merge($config['admin_allowed_filters'] ?? []);
        }

        $filtersCollection = new Collection($filters);

        $filtersCollection->each(function ($value, string $key) use (&$expressions, $allowed) {
            if (! $allowed->contains($key)) {
                return;
            }

            $expression = $this->normaliseFilterExpression($key, $value);

            if ($expression !== null) {
                $expressions->push($expression);
            }
        });

        return $expressions
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, string> $filters
     * @return array<string, mixed>
     */
    protected function buildSearchPayload(
        string $scope,
        array $config,
        SearchQueryParameters $parameters,
        array $filters
    ): array {
        $payload = [
            'q' => $parameters->query,
            'filter' => $filters,
            'limit' => $parameters->perPage,
            'offset' => max(0, ($parameters->page - 1) * $parameters->perPage),
            'attributesToHighlight' => $this->attributesToHighlight($scope),
            'highlightPreTag' => '<mark data-variant="search">',
            'highlightPostTag' => '</mark>',
            'showMatchesPosition' => false,
        ];

        $sort = $this->resolveSort($config, $parameters->sort);

        if ($sort !== null) {
            $payload['sort'] = [$sort];
        }

        if (! empty($config['facets'])) {
            $payload['facets'] = $config['facets'];
        }

        return $payload;
    }

    protected function resolveSort(array $config, ?string $requested): ?string
    {
        $allowed = $config['allowed_sorts'] ?? [];

        if ($requested) {
            if ($this->isSortAllowed($requested, $allowed)) {
                return $requested;
            }
        }

        $default = $config['default_sort'] ?? null;

        return $default && $this->isSortAllowed($default, $allowed)
            ? $default
            : null;
    }

    protected function isSortAllowed(string $sort, array $allowed): bool
    {
        if (empty($allowed)) {
            return false;
        }

        [$field] = explode(':', $sort . ':');

        return in_array($field, $allowed, true);
    }

    /**
     * @param mixed $value
     */
    protected function normaliseFilterExpression(string $field, $value): ?string
    {
        if (is_array($value)) {
            $values = array_filter(array_map(fn ($v) => $this->escapeFilterValue($v), $value));

            if (empty($values)) {
                return null;
            }

            return sprintf('%s IN [%s]', $field, implode(', ', $values));
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return sprintf('%s = %s', $field, $value);
        }

        return sprintf("%s = '%s'", $field, $this->escapeString((string) $value));
    }

    protected function escapeFilterValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return sprintf("'%s'", $this->escapeString((string) $value));
    }

    protected function escapeString(string $value): string
    {
        return str_replace("'", "\'", trim($value));
    }

    /**
     * @param array<int, array<string, mixed>> $hits
     * @return array<int, array<string, mixed>>
     */
    protected function formatHits(array $hits, string $scope): array
    {
        return collect($hits)
            ->map(function (array $hit) use ($scope) {
                $formatted = [
                    'id' => Arr::get($hit, 'id'),
                    'type' => Arr::get($hit, 'type', $scope),
                    'attributes' => Arr::except($hit, ['_formatted']),
                    'highlights' => Arr::get($hit, '_formatted', []),
                ];

                return $formatted;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function attributesToHighlight(string $scope): array
    {
        return match ($scope) {
            'communities' => ['name', 'description', 'tags'],
            'posts' => ['title', 'body', 'topics'],
            'members' => ['name', 'headline', 'skills'],
            default => ['name'],
        };
    }
}

