<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\Services\CommunityEndToEndHarness;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class CommunityE2ESetupCommand extends Command
{
    protected $signature = 'community:e2e:setup
        {--fresh : Run migrate:fresh before executing the scenario}
        {--skip-seed : Do not run the baseline community seeder}
        {--skip-feature : Do not toggle the community_profile_activity flag}
        {--skip-run : Prepare the environment without executing the harness}
        {--report=testing/community_flow_report.json : Relative storage path for the JSON report}';

    protected $description = 'Prepare the application for browser E2E tests and execute the community smoke scenario.';

    public function handle(CommunityEndToEndHarness $harness): int
    {
        if ($this->option('fresh')) {
            $this->info('Refreshing database for a clean E2E run...');
            $this->call('migrate:fresh', ['--force' => true]);
        }

        if (! $this->option('skip-seed')) {
            $this->info('Seeding baseline community configuration...');
            $this->call('community:seed-baseline', ['--force' => true]);
        }

        if (! $this->option('skip-feature')) {
            $this->info('Ensuring profile activity feature flag is enabled at 100%.');
            $this->call('community:enable-feature', [
                '--flag' => 'community_profile_activity',
                '--percentage' => 100,
                '--segment' => 'internal,e2e',
                '--force' => true,
            ]);
        }

        if ($this->option('skip-run')) {
            $this->info('Environment prepared. Harness execution was skipped by request.');

            return self::SUCCESS;
        }

        $this->info('Executing community end-to-end harness...');
        $result = $harness->execute();

        $reportRelativePath = trim((string) $this->option('report')) ?: 'testing/community_flow_report.json';
        $reportPath = ltrim(str_replace('..', '', $reportRelativePath), '/');
        $directory = trim(dirname($reportPath), '.');

        if ($directory !== '') {
            Storage::disk('local')->makeDirectory($directory);
        }

        $payload = $result->toArray();
        $payload['meta'] = Arr::add($payload['meta'], 'report_path', 'storage/app/' . $reportPath);

        Storage::disk('local')->put(
            $reportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );

        $this->table(
            ['Community', 'Members', 'Subscription Status', 'Points'],
            [[
                $payload['community']['slug'] ?? 'n/a',
                $payload['community']['member_count'] ?? 'n/a',
                $payload['subscription']['status'] ?? 'n/a',
                $payload['points']['balance'] ?? 'n/a',
            ]]
        );

        $this->info(sprintf('E2E harness completed. Report stored at storage/app/%s', $reportPath));

        return self::SUCCESS;
    }
}
