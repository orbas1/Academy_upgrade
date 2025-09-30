<?php

namespace App\Console\Commands;

use App\Support\Migrations\MigrationPhase;
use App\Support\Migrations\MigrationPlanner;
use App\Support\Migrations\MigrationStep;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ShowMigrationPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration:plan {plan? : The key of the migration plan to inspect} {--phase=* : Limit output to specific phase keys}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render the expand/backfill/contract strategy defined in config/migrations.php.';

    public function handle(MigrationPlanner $planner): int
    {
        $planKey = $this->argument('plan');
        $phaseFilter = Collection::make($this->option('phase'))
            ->filter()
            ->map(fn ($phase) => strtolower((string) $phase))
            ->all();

        if ($planKey) {
            if (! $planner->has($planKey)) {
                $this->error("Migration plan [{$planKey}] was not found.");

                return self::FAILURE;
            }

            $plan = $planner->get($planKey);
            $this->renderPlan($plan->name, Collection::make([$plan]), $phaseFilter);

            return self::SUCCESS;
        }

        $this->renderPlan('All migration plans', $planner->plans(), $phaseFilter);

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, \App\Support\Migrations\MigrationPlan>  $plans
     * @param  array<int, string>  $phaseFilter
     */
    private function renderPlan(string $title, Collection $plans, array $phaseFilter = []): void
    {
        $this->info($title);
        $this->line(str_repeat('-', mb_strlen($title)));

        foreach ($plans as $plan) {
            $this->line("Plan: <info>{$plan->name}</info> ({$plan->key})");
            $this->line("Owners: " . implode(', ', $plan->serviceOwners));
            if ($plan->dependencies !== []) {
                $this->line('Dependencies: ' . implode(', ', $plan->dependencies));
            }
            $this->line('Feature flags:');
            foreach ($plan->featureFlags as $surface => $flags) {
                $flags = $flags ?: ['(none)'];
                $this->line("  - {$surface}: " . implode(', ', $flags));
            }
            $this->line('Minimum versions:');
            foreach ($plan->minimumVersions as $surface => $version) {
                $this->line("  - {$surface}: " . ($version ?: 'n/a'));
            }
            $this->line('');

            /** @var MigrationPhase $phase */
            foreach ($plan->phases as $phase) {
                if ($phaseFilter !== [] && ! in_array(strtolower($phase->key), $phaseFilter, true)) {
                    continue;
                }

                $this->line("  Phase: <comment>{$phase->name}</comment> ({$phase->type})");
                if ($phase->stabilityWindowDays) {
                    $this->line("    Stability window: {$phase->stabilityWindowDays} days");
                } elseif ($plan->defaultStabilityWindowDays) {
                    $this->line("    Stability window: {$plan->defaultStabilityWindowDays} days (default)");
                }

                /** @var MigrationStep $step */
                foreach ($phase->steps as $step) {
                    $this->line("    - Step: {$step->name} ({$step->key})");
                    $this->line("      Summary: {$step->summary}");
                    $this->line('      Operations:');
                    foreach ($step->operations as $operation) {
                        $this->line("        • {$operation}");
                    }

                    if ($step->backfill !== []) {
                        $this->line('      Backfill strategy:');
                        foreach ($step->backfill as $item) {
                            $this->line("        • {$item}");
                        }
                    }

                    $this->line('      Verification:');
                    foreach ($step->verification as $item) {
                        $this->line("        • {$item}");
                    }

                    $this->line('      Rollback:');
                    foreach ($step->rollback as $item) {
                        $this->line("        • {$item}");
                    }

                    $this->line('      Owners: ' . implode(', ', $step->owners));
                    if ($step->dependencies !== []) {
                        $this->line('      Dependencies: ' . implode(', ', $step->dependencies));
                    }
                    if ($step->stabilityWindowDays) {
                        $this->line("      Stability window override: {$step->stabilityWindowDays} days");
                    }
                }

                $this->line('');
            }

            $this->line(str_repeat('-', 72));
        }
    }
}
