<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

use Illuminate\Support\Collection;

final class RequirementResult
{
    /**
     * @param  array<int, CheckResult>  $checks
     * @param  array<int, array<string, string>>  $evidence
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public readonly RequirementDefinition $definition,
        public readonly array $checks,
        public readonly float $completion,
        public readonly float $quality,
        public readonly string $status,
        public readonly array $evidence,
        public readonly array $tags,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->definition->id,
            'title' => $this->definition->title,
            'description' => $this->definition->description,
            'tags' => $this->tags,
            'status' => $this->status,
            'completion' => $this->completion,
            'quality' => $this->quality,
            'checks' => array_map(fn (CheckResult $result) => $result->toArray(), $this->checks),
            'evidence' => $this->evidence,
        ];
    }

    public static function calculateCompletion(Collection $checks): float
    {
        $totalWeight = $checks->sum(fn (CheckResult $result) => $result->definition->weight);
        if ($totalWeight <= 0) {
            return 0.0;
        }

        $passedWeight = $checks
            ->filter(fn (CheckResult $result) => $result->passed)
            ->sum(fn (CheckResult $result) => $result->definition->weight);

        return round(($passedWeight / $totalWeight) * 100, 2);
    }
}
