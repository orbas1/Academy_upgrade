<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunitiesQualityGateCommand extends Command
{
    protected $signature = 'communities:quality-gate {--json : Output the evaluation summary as JSON}';

    protected $description = 'Validates Orbas Learn community prerequisites before staging/production rollouts.';

    public function handle(): int
    {
        $report = [
            'schema' => $this->checkSchema(),
            'seeders' => $this->checkSeedData(),
            'config' => $this->checkConfig(),
        ];

        $hasFailures = array_reduce(
            $report,
            fn (bool $carry, array $section): bool => $carry || $section['status'] === false,
            false
        );

        if ($this->option('json')) {
            $this->line(json_encode([
                'passed' => ! $hasFailures,
                'sections' => $report,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $hasFailures ? self::FAILURE : self::SUCCESS;
        }

        foreach ($report as $section => $result) {
            $heading = sprintf('[%s] %s', strtoupper($section), $result['summary']);

            if ($result['status']) {
                $this->info($heading);
                foreach ($result['details'] as $detail) {
                    $this->line('  • '.$detail);
                }

                continue;
            }

            $this->error($heading);
            foreach ($result['details'] as $detail) {
                $this->warn('  • '.$detail);
            }
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    private function checkSchema(): array
    {
        $expectedTables = [
            'communities' => ['slug', 'name', 'visibility', 'join_policy'],
            'community_members' => ['community_id', 'user_id', 'status', 'last_seen_at'],
            'community_posts' => ['community_id', 'author_id', 'visibility', 'published_at'],
            'community_post_comments' => ['post_id', 'author_id', 'body_md'],
            'community_post_likes' => ['post_id', 'user_id', 'reaction'],
            'community_comment_likes' => ['comment_id', 'user_id'],
            'community_points_ledger' => ['community_id', 'member_id', 'action', 'points_delta'],
            'community_levels' => ['community_id', 'level', 'points_required'],
            'community_points_rules' => ['community_id', 'action', 'points'],
            'community_leaderboards' => ['community_id', 'period', 'entries'],
            'community_subscription_tiers' => ['community_id', 'name', 'price_cents'],
            'community_subscriptions' => ['community_id', 'user_id', 'status'],
            'community_paywall_access' => ['community_id', 'user_id', 'access_starts_at'],
            'community_single_purchases' => ['community_id', 'user_id', 'purchased_at'],
            'community_follows' => ['community_id', 'follower_id', 'followable_type'],
        ];

        $issues = [];

        foreach ($expectedTables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $issues[] = sprintf('Missing table `%s` – run the community migrations.', $table);
                continue;
            }

            $existing = Schema::getColumnListing($table);
            $missingColumns = array_values(array_diff($columns, $existing));

            if ($missingColumns !== []) {
                $issues[] = sprintf(
                    'Table `%s` is missing required columns: %s',
                    $table,
                    implode(', ', $missingColumns)
                );
            }
        }

        return [
            'status' => $issues === [],
            'summary' => $issues === []
                ? 'Schema baseline verified.'
                : 'Schema drift detected for community domain.',
            'details' => $issues === []
                ? ['All community tables and critical columns are present.']
                : $issues,
        ];
    }

    private function checkSeedData(): array
    {
        $issues = [];

        $categoryCount = DB::table('community_categories')->count();
        if ($categoryCount < 5) {
            $issues[] = sprintf('Expected at least 5 community categories, found %d.', $categoryCount);
        }

        $levelCount = DB::table('community_levels')->whereNull('community_id')->count();
        if ($levelCount < 4) {
            $issues[] = sprintf('Expected baseline community levels (>=4), found %d.', $levelCount);
        }

        $pointsRuleCount = DB::table('community_points_rules')->whereNull('community_id')->count();
        if ($pointsRuleCount < 5) {
            $issues[] = sprintf('Expected baseline points rules (>=5), found %d.', $pointsRuleCount);
        }

        return [
            'status' => $issues === [],
            'summary' => $issues === []
                ? 'Seed data verified.'
                : 'Baseline seed data missing for Orbas Learn communities.',
            'details' => $issues === []
                ? ['Community categories, levels, and points rules are populated.']
                : $issues,
        ];
    }

    private function checkConfig(): array
    {
        $issues = [];

        $stripeSecret = Config::get('stripe.secret_key');
        if (empty($stripeSecret)) {
            $issues[] = 'Stripe secret key missing – set STRIPE_SECRET for billing flows.';
        }

        $queueDriver = Config::get('queue.default');
        if ($queueDriver === 'sync') {
            $issues[] = 'Queue driver is `sync`; use redis or database to process community jobs.';
        }

        $cacheStore = Config::get('cache.default');
        if ($cacheStore === 'file') {
            $issues[] = 'Cache store is `file`; configure redis/memcached for multi-node readiness.';
        }

        $searchDriver = Config::get('search.driver');
        if ($searchDriver !== 'meilisearch') {
            $issues[] = sprintf('Search driver `%s` configured – expected `meilisearch`.', (string) $searchDriver);
        }

        $meilisearchHost = Config::get('search.meilisearch.host');
        if (empty($meilisearchHost)) {
            $issues[] = 'Meilisearch host missing – set MEILISEARCH_HOST.';
        }

        return [
            'status' => $issues === [],
            'summary' => $issues === []
                ? 'Environment configuration verified.'
                : 'Environment configuration incomplete for communities rollout.',
            'details' => $issues === []
                ? ['Stripe, queues, cache, and search settings meet launch requirements.']
                : $issues,
        ];
    }
}
