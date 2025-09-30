<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\Services\CommunityThreadLifecycleService;
use Illuminate\Console\Command;

class CommunitiesAutoArchiveCommand extends Command
{
    protected $signature = 'communities:auto-archive {--community=} {--dry-run} {--chunk=}';

    protected $description = 'Archive inactive community threads and optionally preview the impact.';

    public function __construct(private readonly CommunityThreadLifecycleService $lifecycleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $communityId = $this->option('community');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = $this->option('chunk');
        $chunkSize = $chunk !== null ? max(1, (int) $chunk) : null;

        $result = $this->lifecycleService->archiveInactiveThreads(
            $communityId !== null ? (int) $communityId : null,
            $dryRun,
            $chunkSize
        );

        if ($dryRun) {
            $this->info(sprintf(
                '[DRY RUN] %d posts would be archived (inactive before %s, no activity since %s).',
                $result['candidates'] ?? 0,
                $result['inactive_threshold'] ?? 'n/a',
                $result['recent_activity_cutoff'] ?? 'n/a'
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Archived %d of %d candidates (inactive before %s, no activity since %s).',
            $result['archived'] ?? 0,
            $result['candidates'] ?? 0,
            $result['inactive_threshold'] ?? 'n/a',
            $result['recent_activity_cutoff'] ?? 'n/a'
        ));

        return self::SUCCESS;
    }
}
