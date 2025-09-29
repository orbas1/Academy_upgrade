<?php

declare(strict_types=1);

namespace App\Domain\Search\Data;

final class SearchQueryParameters
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly string $scope,
        public readonly string $query,
        public readonly array $filters = [],
        public readonly ?string $sort = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
    }
}

