<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Search\Data\SearchQueryParameters;
use App\Domain\Search\Models\AdminSavedSearch;
use App\Domain\Search\Models\SearchAuditLog;
use App\Models\User;

class AdminSearchService
{
    public function __construct(
        protected SearchQueryService $queryService
    ) {
    }

    /**
     * @param array<int, string> $visibilityFilters
     * @return array<string, mixed>
     */
    public function runSearch(
        SearchQueryParameters $parameters,
        User $user,
        array $visibilityFilters = []
    ): array {
        $result = $this->queryService->search($parameters, $visibilityFilters, true);

        SearchAuditLog::create([
            'user_id' => $user->getKey(),
            'scope' => $parameters->scope,
            'query' => $parameters->query,
            'filters' => $parameters->filters,
            'result_count' => $this->extractResultCount($result, $parameters->scope),
            'is_admin' => true,
            'executed_at' => now(),
        ]);

        return $result;
    }

    public function markTriggered(AdminSavedSearch $savedSearch): void
    {
        $savedSearch->forceFill([
            'last_triggered_at' => now(),
        ])->save();
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function extractResultCount(array $result, string $scope): int
    {
        if (($result['meta']['scope'] ?? null) === 'all') {
            $total = 0;

            foreach ($result['data'] as $payload) {
                $total += (int) ($payload['total'] ?? 0);
            }

            return $total;
        }

        if (isset($result['data']['total'])) {
            return (int) $result['data']['total'];
        }

        return (int) ($result['total'] ?? 0);
    }
}

