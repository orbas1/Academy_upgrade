<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\Models\CommunityPost;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CommunitiesHealthMonitorCommand extends Command
{
    protected $signature = 'communities:health-monitor';

    protected $description = 'Monitors moderation queue depth and error rates to alert operations.';

    public function handle(): int
    {
        $threshold = (int) Config::get('communities.automation.health.queue_threshold', 25);
        $errorThreshold = (float) Config::get('communities.automation.health.error_rate_threshold', 0.05);
        $webhook = Config::get('communities.automation.health.notification_webhook');

        $pendingModeration = CommunityPost::query()
            ->whereRaw("JSON_EXTRACT(COALESCE(metadata, '{}'), '$.moderation.status') = '" . json_encode('pending') . "'")
            ->count();

        $windowStart = Carbon::now()->subMinutes(30);
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $windowStart)
            ->count();
        $processedJobs = DB::table('jobs')
            ->where('reserved_at', '>=', $windowStart->timestamp)
            ->count();
        $totalJobs = $failedJobs + $processedJobs;
        $errorRate = $totalJobs > 0 ? $failedJobs / $totalJobs : 0.0;

        $alerts = [];

        if ($pendingModeration > $threshold) {
            $alerts[] = sprintf('Moderation queue exceeds threshold (%d pending, threshold %d).', $pendingModeration, $threshold);
        }

        if ($errorRate > $errorThreshold) {
            $alerts[] = sprintf('Queue error rate is %.2f%% in last 30m (threshold %.2f%%).', $errorRate * 100, $errorThreshold * 100);
        }

        if ($alerts === []) {
            $this->info('Communities health is within thresholds.');

            return self::SUCCESS;
        }

        $message = implode('\n', $alerts);
        Log::warning('communities.health.alert', ['message' => $message]);

        if ($webhook) {
            Http::post($webhook, [
                'text' => sprintf(':rotating_light: Communities health alert:%s%s', PHP_EOL, $message),
            ]);
        }

        foreach ($alerts as $alert) {
            $this->warn($alert);
        }

        return self::SUCCESS;
    }
}
