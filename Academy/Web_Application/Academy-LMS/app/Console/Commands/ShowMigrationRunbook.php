<?php

namespace App\Console\Commands;

use App\Support\Migrations\MigrationRunbook;
use App\Support\Migrations\MigrationRunbookRegistry;
use App\Support\Migrations\MigrationRunbookStep;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ShowMigrationRunbook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migration:runbook {runbook? : The key of the migration runbook to inspect} {--step=* : Limit output to specific step keys}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render the migration runbooks defined in config/migration_runbooks.php.';

    public function handle(MigrationRunbookRegistry $registry): int
    {
        $runbookKey = $this->argument('runbook');
        $stepFilter = Collection::make($this->option('step'))
            ->filter()
            ->map(fn ($step) => strtolower((string) $step))
            ->all();

        if ($runbookKey) {
            if (! $registry->has($runbookKey)) {
                $this->error("Migration runbook [{$runbookKey}] was not found.");

                return self::FAILURE;
            }

            $runbook = $registry->get($runbookKey);
            $this->renderRunbooks(Collection::make([$runbook]), $stepFilter);

            return self::SUCCESS;
        }

        $this->renderRunbooks($registry->runbooks(), $stepFilter);

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, MigrationRunbook>  $runbooks
     * @param  array<int, string>  $stepFilter
     */
    private function renderRunbooks(Collection $runbooks, array $stepFilter): void
    {
        foreach ($runbooks as $runbook) {
            $this->info($runbook->name . " ({$runbook->key})");
            if ($runbook->description) {
                $this->line($runbook->description);
            }
            if ($runbook->planKey) {
                $this->line("Plan key: {$runbook->planKey}");
            }
            if ($runbook->serviceOwner !== []) {
                $this->line('Service owner: ' . implode(', ', $runbook->serviceOwner));
            }
            if ($runbook->approvers !== []) {
                $this->line('Approvers: ' . implode(', ', $runbook->approvers));
            }
            if ($runbook->communicationChannels !== []) {
                $this->line('Comm channels: ' . implode(', ', $runbook->communicationChannels));
            }
            if ($runbook->maintenanceWindowMinutes) {
                $this->line('Maintenance window: ' . $runbook->maintenanceWindowMinutes . ' minutes');
            }
            $this->line(str_repeat('-', 72));

            /** @var MigrationRunbookStep $step */
            foreach ($runbook->steps as $step) {
                if ($stepFilter !== [] && ! in_array(strtolower($step->key), $stepFilter, true)) {
                    continue;
                }

                $this->line("Step: <comment>{$step->name}</comment> ({$step->key})");
                $this->line('  Type: ' . $step->type);
                if ($step->ownerRoles !== []) {
                    $this->line('  Owners: ' . implode(', ', $step->ownerRoles));
                }
                if ($step->maintenanceWindowMinutes) {
                    $this->line('  Maintenance allocation: ' . $step->maintenanceWindowMinutes . ' minutes');
                }
                if ($step->expectedRuntimeMinutes) {
                    $this->line('  Expected runtime: ' . $step->expectedRuntimeMinutes . ' minutes');
                }
                if ($step->dependencies !== []) {
                    $this->line('  Dependencies: ' . implode(', ', $step->dependencies));
                }
                if ($step->relatedMigrations !== []) {
                    $this->line('  Related migrations: ' . implode(', ', $step->relatedMigrations));
                }
                if ($step->relatedCommands !== []) {
                    $this->line('  Commands: ' . implode(', ', $step->relatedCommands));
                }
                $this->renderSection('Prechecks', $step->prechecks);
                $this->renderSection('Execution', $step->execution);
                $this->renderSection('Verification', $step->verification);
                $this->renderSection('Rollback', $step->rollback);
                if ($step->telemetry !== []) {
                    $this->renderSection('Telemetry', $step->telemetry);
                }
                if ($step->notes) {
                    $this->line('  Notes: ' . $step->notes);
                }
                $this->line(str_repeat('-', 72));
            }
        }
    }

    /**
     * @param  array<int, string>  $items
     */
    private function renderSection(string $title, array $items): void
    {
        $this->line("  {$title}:");
        foreach ($items as $item) {
            $this->line("    • {$item}");
        }
        if ($items === []) {
            $this->line('    • (none)');
        }
    }
}
