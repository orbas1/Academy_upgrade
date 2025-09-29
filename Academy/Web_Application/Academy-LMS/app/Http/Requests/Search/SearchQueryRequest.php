<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use App\Domain\Search\Data\SearchQueryData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SearchQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $indexes = array_keys((array) config('search.meilisearch.indexes', []));
        $maxLimit = (int) config('search.query.max_limit', 50);

        return [
            'index' => ['required', 'string', Rule::in($indexes)],
            'query' => ['nullable', 'string', 'max:200'],
            'visibility_token' => ['nullable', 'string'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['string', 'max:255'],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['string', 'max:120'],
            'facets' => ['nullable', 'array'],
            'facets.*' => ['string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $maxLimit],
            'page' => ['nullable', 'integer', 'min:1'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaultLimit = (int) config('search.query.default_limit', 20);

        $this->merge([
            'limit' => $this->input('limit', $defaultLimit),
            'page' => $this->input('page', 1),
        ]);
    }

    public function toSearchQueryData(): SearchQueryData
    {
        $validated = $this->validated();

        $limit = (int) Arr::get($validated, 'limit', (int) config('search.query.default_limit', 20));
        $page = max(1, (int) Arr::get($validated, 'page', 1));
        $offset = ($page - 1) * $limit;

        if (! empty($validated['cursor'])) {
            [$cursorIndex, $cursorOffset, $cursorLimit] = $this->decodeCursor((string) $validated['cursor']);
            if ($cursorIndex === $validated['index']) {
                $offset = $cursorOffset;
                $limit = $cursorLimit;
            }
        }

        return new SearchQueryData(
            index: (string) $validated['index'],
            query: (string) ($validated['query'] ?? ''),
            visibilityToken: $validated['visibility_token'] ?? null,
            filters: $this->normaliseStringArray($validated['filters'] ?? []),
            enforcedFilters: [],
            sort: $this->normaliseStringArray($validated['sort'] ?? []),
            facets: $this->normaliseStringArray($validated['facets'] ?? []),
            limit: $limit,
            offset: $offset,
            cursor: $validated['cursor'] ?? null,
            user: $this->user('sanctum') ?? $this->user(),
            bypassVisibility: false,
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
     * @return array{0: string, 1: int, 2: int}
     */
    protected function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            return ['', 0, (int) config('search.query.default_limit', 20)];
        }

        $payload = json_decode($decoded, true);

        if (! is_array($payload)) {
            return ['', 0, (int) config('search.query.default_limit', 20)];
        }

        return [
            (string) Arr::get($payload, 'i', ''),
            (int) Arr::get($payload, 'o', 0),
            (int) Arr::get($payload, 'l', (int) config('search.query.default_limit', 20)),
        ];
    }
}

