<?php

namespace App\Console\Commands\Compliance;

use App\Services\Security\DataRetentionService;
use Illuminate\Console\Command;

class PrunePersonalData extends Command
{
    protected $signature = 'compliance:prune-personal-data {--dry-run : Only report rows that would be deleted}';

    protected $description = 'Apply data protection retention policies for audit logs, device sessions, and personal access tokens.';

    public function handle(DataRetentionService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && $this->shouldRunBackup()) {
            $profile = config('security.data_protection.backup.profile', 'media');
            $this->info("Running backup profile [{$profile}] before pruning sensitive data...");
            $this->call('storage:backup', ['profile' => $profile]);
        }

        $results = $service->prune($dryRun);

        $this->components->twoColumnDetail('Audit logs pruned', (string) $results['audit_logs_deleted']);
        $this->components->twoColumnDetail('Device sessions removed', (string) $results['device_sessions_deleted']);
        $this->components->twoColumnDetail('Device access tokens removed', (string) $results['device_tokens_deleted']);
        $this->components->twoColumnDetail('Personal access tokens removed', (string) $results['personal_access_tokens_deleted']);
        $this->components->twoColumnDetail('Upload scans pruned', (string) $results['upload_scans_deleted']);
        $this->components->twoColumnDetail('Quarantine files removed', (string) $results['quarantine_files_removed']);
        $this->components->twoColumnDetail('Export archives removed', (string) $results['export_archives_deleted']);

        if ($dryRun) {
            $this->comment('Dry run complete. No records were deleted.');

            return self::SUCCESS;
        }

        $this->info('Data protection pruning finished successfully.');

        return self::SUCCESS;
    }

    private function shouldRunBackup(): bool
    {
        if (! config('security.data_protection.backup.enabled', false)) {
            return false;
        }

        return config('app.env') !== 'testing';
    }
}
