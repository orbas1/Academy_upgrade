<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Analytics\Models\AnalyticsEvent;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class PruneAnalyticsEventsCommand extends Command
{
    protected $signature = 'analytics:prune {--days=}';

    protected $description = 'Remove analytics events older than the configured retention period.';

    public function handle(): int
    {
        $days = $this->option('days');
        $retentionDays = $days !== null ? (int) $days : (int) Config::get('analytics.retention_days', 395);
        $cutoff = CarbonImmutable::now()->subDays($retentionDays);

        $deleted = AnalyticsEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $this->info(sprintf('Pruned %d analytics events prior to %s.', $deleted, $cutoff->toDateTimeString()));

        return self::SUCCESS;
    }
}
