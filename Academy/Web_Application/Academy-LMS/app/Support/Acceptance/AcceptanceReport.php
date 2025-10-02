<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

use Illuminate\Support\Collection;

final class AcceptanceReport
{
    /**
     * @param  array<int, RequirementResult>  $requirements
     */
    public function __construct(
        public readonly array $requirements,
        public readonly array $summary,
        public readonly string $generatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'summary' => $this->summary,
            'requirements' => array_map(fn (RequirementResult $result) => $result->toArray(), $this->requirements),
        ];
    }

    public static function buildSummary(Collection $requirements): array
    {
        $totalChecks = $requirements->sum(function (RequirementResult $result) {
            return collect($result->checks)->sum(fn (CheckResult $check) => $check->definition->weight);
        });

        $passedChecks = $requirements->sum(function (RequirementResult $result) {
            return collect($result->checks)
                ->filter(fn (CheckResult $check) => $check->passed)
                ->sum(fn (CheckResult $check) => $check->definition->weight);
        });

        $completion = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : 0.0;
        $quality = $requirements->isNotEmpty()
            ? round((
                $requirements->filter(fn (RequirementResult $result) => $result->status === 'pass')->count()
                / $requirements->count()
            ) * 100, 2)
            : 0.0;

        return [
            'requirements_total' => $requirements->count(),
            'requirements_passed' => $requirements->filter(fn (RequirementResult $result) => $result->status === 'pass')->count(),
            'checks_total' => $totalChecks,
            'checks_passed' => $passedChecks,
            'completion' => $completion,
            'quality' => $quality,
        ];
    }
}
