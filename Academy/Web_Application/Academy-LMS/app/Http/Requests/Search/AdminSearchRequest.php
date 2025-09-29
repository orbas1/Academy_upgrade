<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use App\Domain\Search\Data\SearchQueryData;
use App\Domain\Search\Models\SearchSavedQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AdminSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('search.audit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $indexes = array_keys((array) config('search.meilisearch.indexes', []));
        $flags = array_keys((array) config('search.admin_tools.flag_filters', []));
        $maxLimit = (int) config('search.admin_tools.max_limit', 100);

        return [
            'index' => ['nullable', 'string', Rule::in($indexes)],
            'query' => ['nullable', 'string', 'max:200'],
            'visibility_token' => ['nullable', 'string'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['string', 'max:255'],
            'flags' => ['nullable', 'array'],
            'flags.*' => ['string', Rule::in($flags)],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $maxLimit],
            'cursor' => ['nullable', 'string'],
            'saved_search_id' => ['nullable', 'integer', 'exists:search_saved_queries,id'],
            'export' => ['nullable', 'string', Rule::in(['none', 'csv'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaultLimit = (int) config('search.admin_tools.default_limit', 50);

        $this->merge([
            'limit' => $this->input('limit', $defaultLimit),
            'export' => $this->input('export', 'none'),
        ]);
    }

    public function toSearchQueryData(?SearchSavedQuery $savedQuery = null): SearchQueryData
    {
        $validated = $this->validated();

        if ($savedQuery !== null) {
            $validated = array_merge(
                [
                    'index' => $savedQuery->index,
                    'query' => $savedQuery->query,
                    'filters' => $savedQuery->filters ?? [],
                    'flags' => $savedQuery->flags ?? [],
                    'sort' => $savedQuery->sort ?? [],
                ],
                $validated
            );
        }

        $limit = (int) Arr::get($validated, 'limit', (int) config('search.admin_tools.default_limit', 50));
        $offset = 0;

        if (! empty($validated['cursor'])) {
            [$cursorIndex, $cursorOffset, $cursorLimit] = $this->decodeCursor((string) $validated['cursor']);
            if (($validated['index'] ?? null) === $cursorIndex) {
                $offset = $cursorOffset;
                $limit = $cursorLimit;
            }
        }

        return new SearchQueryData(
            index: (string) ($validated['index'] ?? $savedQuery?->index ?? 'posts'),
            query: (string) ($validated['query'] ?? ''),
            visibilityToken: $validated['visibility_token'] ?? null,
            filters: $this->normaliseStringArray($validated['filters'] ?? []),
            enforcedFilters: $this->resolveFlagFilters($this->normaliseStringArray($validated['flags'] ?? [])),
            sort: $this->normaliseStringArray($validated['sort'] ?? []),
            facets: [],
            limit: $limit,
            offset: $offset,
            cursor: $validated['cursor'] ?? null,
            user: $this->user('sanctum') ?? $this->user(),
            bypassVisibility: true,
        );
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    protected function normaliseStringArray(array $values): array
    {
        return array_values(array_filter(array_map(static fn ($value) => (string) $value, $values)));
    }

    /**
     * @param array<int, string> $flags
     * @return array<int, string>
     */
    protected function resolveFlagFilters(array $flags): array
    {
        $configured = (array) config('search.admin_tools.flag_filters', []);

        return array_values(array_filter(array_map(
            static fn (string $flag) => $configured[$flag] ?? null,
            $flags
        )));
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    protected function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            return ['', 0, (int) config('search.admin_tools.default_limit', 50)];
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            return ['', 0, (int) config('search.admin_tools.default_limit', 50)];
        }

        return [
            (string) Arr::get($payload, 'i', ''),
            (int) Arr::get($payload, 'o', 0),
            (int) Arr::get($payload, 'l', (int) config('search.admin_tools.default_limit', 50)),
        ];
    }
}

