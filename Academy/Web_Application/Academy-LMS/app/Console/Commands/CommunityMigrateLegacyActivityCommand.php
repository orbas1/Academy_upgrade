<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\DTO\ProfileActivityMigrationReport;
use App\Domain\Communities\Services\ProfileActivityMigrationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CommunityMigrateLegacyActivityCommand extends Command
{
    protected $signature = 'community:migrate-legacy-activity
        {--chunk=500 : Number of records processed per batch}
        {--since= : Only migrate activity that occurred on or after the provided ISO8601 timestamp}
        {--dry-run : Report intended changes without writing}';

    protected $description = 'Backfill profile activity projections from historic community posts, comments, and completions.';

    public function __construct(private readonly ProfileActivityMigrationService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $sinceOption = $this->option('since');
        $since = null;

        if ($sinceOption) {
            try {
                $since = Carbon::parse($sinceOption);
            } catch (\Exception $exception) {
                $this->error(sprintf('Invalid --since value [%s]: %s', $sinceOption, $exception->getMessage()));

                return self::FAILURE;
            }
        }

        $this->info(sprintf(
            'Migrating legacy activity (chunk: %d, dry-run: %s%s).',
            $chunk,
            $dryRun ? 'yes' : 'no',
            $since ? ', since ' . $since->toIso8601String() : ''
        ));

        $report = $this->service->migrate($dryRun, $chunk, $since);

        $this->renderReport($report, $dryRun);

        return self::SUCCESS;
    }

    private function renderReport(ProfileActivityMigrationReport $report, bool $dryRun): void
    {
        $headers = ['Metric', 'Value'];
        $rows = [
            ['Community posts processed', $report->postsProcessed],
            ['Community comments processed', $report->commentsProcessed],
            ['Course completions processed', $report->completionsProcessed],
            ['Activities created', $report->recordsCreated],
            ['Activities updated', $report->recordsUpdated],
            ['Records skipped', $report->recordsSkipped],
        ];

        $this->table($headers, $rows);

        if ($dryRun) {
            $this->comment('Dry-run mode enabled; no records were written.');
        }
    }
}
