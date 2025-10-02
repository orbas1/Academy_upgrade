<?php

declare(strict_types=1);

namespace App\Support\Acceptance;

use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

final class AcceptanceReportService
{
    public function __construct(private readonly Filesystem $filesystem)
    {
        // Dependency injected via container.
    }

    public function generate(): AcceptanceReport
    {
        $definitions = Collection::make(Config::get('acceptance.requirements', []))
            ->map(fn (array $payload) => RequirementDefinition::fromArray($payload));

        $results = $definitions
            ->map(function (RequirementDefinition $definition) {
                $checks = Collection::make($definition->checks)
                    ->map(fn (CheckDefinition $check) => $this->evaluateCheck($check))
                    ->values();

                $completion = RequirementResult::calculateCompletion($checks);
                $quality = $completion;
                $status = $checks->every(fn (CheckResult $result) => $result->passed) ? 'pass' : 'fail';

                return new RequirementResult(
                    $definition,
                    $checks->all(),
                    $completion,
                    $quality,
                    $status,
                    $definition->evidence,
                    $definition->tags,
                );
            })
            ->values();

        $summary = AcceptanceReport::buildSummary($results);

        return new AcceptanceReport(
            $results->all(),
            $summary,
            CarbonImmutable::now()->toIso8601String(),
        );
    }

    private function evaluateCheck(CheckDefinition $definition): CheckResult
    {
        return match ($definition->type) {
            'class' => $this->evaluateClass($definition),
            'file' => $this->evaluateFile($definition),
            'config' => $this->evaluateConfig($definition),
            default => new CheckResult($definition, false, sprintf('Unsupported check type [%s]', $definition->type)),
        };
    }

    private function evaluateClass(CheckDefinition $definition): CheckResult
    {
        $exists = class_exists($definition->identifier);

        return new CheckResult(
            $definition,
            $exists,
            $exists ? null : sprintf('Class [%s] was not autoloadable.', $definition->identifier),
        );
    }

    private function evaluateFile(CheckDefinition $definition): CheckResult
    {
        $relativePath = $definition->identifier;
        $absolutePath = base_path($relativePath);
        $exists = $this->filesystem->isFile($absolutePath);

        return new CheckResult(
            $definition,
            $exists,
            $exists ? null : sprintf('File [%s] is missing.', $relativePath),
        );
    }

    private function evaluateConfig(CheckDefinition $definition): CheckResult
    {
        $value = Config::get($definition->identifier);
        $passed = $value !== null && $value !== '' && $value !== [];

        return new CheckResult(
            $definition,
            $passed,
            $passed ? null : sprintf('Config [%s] is undefined.', $definition->identifier),
        );
    }
}
