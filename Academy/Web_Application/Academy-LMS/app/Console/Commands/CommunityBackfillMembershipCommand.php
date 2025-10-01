<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\DTO\MembershipBackfillReport;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Services\CommunityMembershipBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CommunityBackfillMembershipCommand extends Command
{
    protected $signature = 'community:backfill-membership
        {--source=classrooms : Data source used for inference}
        {--community= : Limit to a specific community ID or slug}
        {--batch=1000 : Number of enrollments processed per chunk}
        {--dry-run : Compute changes without writing to the database}';

    protected $description = 'Backfill community memberships from classroom enrollments with idempotent batching.';

    public function __construct(private readonly CommunityMembershipBackfillService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = (string) $this->option('source');
        if ($source !== 'classrooms') {
            $this->error(sprintf('Unsupported backfill source [%s]. Only "classrooms" is currently supported.', $source));

            return self::FAILURE;
        }

        $batch = max(1, (int) $this->option('batch'));
        $dryRun = (bool) $this->option('dry-run');

        $community = null;
        if ($identifier = $this->option('community')) {
            $community = $this->resolveCommunity($identifier);
            if (! $community) {
                $this->error(sprintf('Community [%s] was not found.', $identifier));

                return self::FAILURE;
            }
        }

        $this->info(sprintf(
            'Starting membership backfill (source: %s, batch: %d, dry-run: %s).',
            $source,
            $batch,
            $dryRun ? 'yes' : 'no'
        ));

        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('processing: %current% communities [%message%]');

        $report = $this->service->backfillFromClassrooms(
            specificCommunity: $community,
            batchSize: $batch,
            dryRun: $dryRun,
            progress: function (Community $community, array $status) use ($progressBar): void {
                $message = $status['status'] ?? 'processed';
                if (isset($status['reason'])) {
                    $message .= ' (' . $status['reason'] . ')';
                }

                $progressBar->setMessage(sprintf('%s', $message));
                $progressBar->advance();
            }
        );

        $progressBar->finish();
        $this->newLine(2);

        $this->renderReport($report);

        return self::SUCCESS;
    }

    private function resolveCommunity(string $identifier): ?Community
    {
        if (is_numeric($identifier)) {
            return Community::find((int) $identifier);
        }

        return Community::where('slug', Str::slug($identifier))->first();
    }

    private function renderReport(MembershipBackfillReport $report): void
    {
        $this->table(
            ['Metric', 'Value'],
            collect($report->toArray())
                ->map(fn ($value, $key) => [Str::headline(str_replace('_', ' ', $key)), $value])
                ->values()
        );
    }
}
