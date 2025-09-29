<?php

declare(strict_types=1);

namespace App\Domain\Search\Data;

use App\Models\User;

final class SearchQueryData
{
    /**
     * @param array<int, string> $filters
     * @param array<int, string> $enforcedFilters
     * @param array<int, string> $sort
     * @param array<int, string> $facets
     */
    public function __construct(
        public readonly string $index,
        public readonly string $query,
        public readonly ?string $visibilityToken,
        public readonly array $filters,
        public readonly array $enforcedFilters,
        public readonly array $sort,
        public readonly array $facets,
        public readonly int $limit,
        public readonly int $offset,
        public readonly ?string $cursor,
        public readonly ?User $user,
        public readonly bool $bypassVisibility = false,
    ) {
    }

    public function withCursor(?string $cursor): self
    {
        return new self(
            index: $this->index,
            query: $this->query,
            visibilityToken: $this->visibilityToken,
            filters: $this->filters,
            enforcedFilters: $this->enforcedFilters,
            sort: $this->sort,
            facets: $this->facets,
            limit: $this->limit,
            offset: $this->offset,
            cursor: $cursor,
            user: $this->user,
            bypassVisibility: $this->bypassVisibility,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'query' => $this->query,
            'visibility_token' => $this->visibilityToken,
            'filters' => $this->filters,
            'enforced_filters' => $this->enforcedFilters,
            'sort' => $this->sort,
            'facets' => $this->facets,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'cursor' => $this->cursor,
            'user_id' => $this->user?->getKey(),
            'bypass_visibility' => $this->bypassVisibility,
        ];
    }
}

