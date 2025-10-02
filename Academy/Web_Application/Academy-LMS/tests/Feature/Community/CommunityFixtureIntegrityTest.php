<?php

namespace Tests\Feature\Community;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CommunityFixtureIntegrityTest extends TestCase
{
    public function test_fixture_payload_matches_manifest(): void
    {
        $fixturePath = base_path('tests/Fixtures/community_fixture_set.json');
        self::assertFileExists($fixturePath, 'Fixture JSON must exist.');

        $payload = json_decode((string) file_get_contents($fixturePath), true);
        self::assertIsArray($payload, 'Fixture payload should decode into an array.');
        self::assertArrayHasKey('communities', $payload);
        self::assertArrayHasKey('global_metrics', $payload);

        $generatedAt = new DateTimeImmutable($payload['generated_at']);
        $communities = Collection::make($payload['communities']);
        self::assertSame(
            $payload['global_metrics']['communities'],
            $communities->count(),
            'Global metric should reflect number of community records.'
        );

        $allEngagementScores = [];
        $totalActiveMembers = 0;
        $totalPaywalledPosts = 0;

        $communities->each(function (array $community) use (&$allEngagementScores, &$totalActiveMembers, &$totalPaywalledPosts, $generatedAt): void {
            $counts = $community['member_counts'];
            self::assertSame(
                $counts['total'],
                $counts['active'] + $counts['pending'] + $counts['banned'],
                'Member counts must balance across statuses.'
            );
            $totalActiveMembers += $counts['active'];

            $timezoneSum = Collection::make($community['timezone_distribution'])
                ->sum(fn (array $entry) => $entry['members']);
            self::assertSame(
                $counts['total'],
                $timezoneSum,
                'Timezone distribution should cover all members.'
            );

            $leaderboardPoints = Collection::make($community['leaderboard'])
                ->pluck('points')
                ->values()
                ->toArray();
            $leaderboardSorted = $leaderboardPoints;
            rsort($leaderboardSorted);
            self::assertSame(
                $leaderboardSorted,
                $leaderboardPoints,
                'Leaderboard must be sorted by points descending.'
            );

            $trendingScores = Collection::make($community['trending_posts'])
                ->map(fn (array $post) => $post['engagement']['score'])
                ->values()
                ->toArray();
            $sortedTrending = $trendingScores;
            rsort($sortedTrending);
            self::assertSame(
                $sortedTrending,
                $trendingScores,
                sprintf('Trending posts for %s must be sorted by score.', $community['slug'])
            );

            foreach ($community['paywalled_posts'] as $post) {
                self::assertNotNull($post['paywall_tier_id'], 'Paywalled posts must include a tier ID.');
                $totalPaywalledPosts++;
            }

            foreach ($community['upcoming_events'] as $event) {
                $eventStart = new DateTimeImmutable($event['starts_at']);
                self::assertGreaterThanOrEqual(
                    $generatedAt,
                    $eventStart,
                    'Upcoming events should start on or after the fixture timestamp.'
                );
            }

            foreach ($community['recent_posts'] as $post) {
                $allEngagementScores[] = $post['engagement']['score'];
            }
        });

        self::assertSame(
            $totalActiveMembers,
            $payload['global_metrics']['active_members'],
            'Active member counts should aggregate to the global metric.'
        );

        self::assertSame(
            $totalPaywalledPosts,
            $payload['global_metrics']['paywalled_posts'],
            'Paywalled post totals should match global metrics.'
        );

        $maxScore = max($allEngagementScores);
        $minScore = min($allEngagementScores);
        $averageScore = round(array_sum($allEngagementScores) / count($allEngagementScores), 2);

        self::assertSame($maxScore, $payload['global_metrics']['engagement']['max_score']);
        self::assertSame($minScore, $payload['global_metrics']['engagement']['min_score']);
        self::assertSame($averageScore, $payload['global_metrics']['engagement']['average_score']);

        $manifestPath = realpath(base_path('../docs/upgrade/testing/fixtures/fixture_manifest.json'));
        self::assertNotFalse($manifestPath, 'Manifest path should resolve.');
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        self::assertIsArray($manifest);

        $fixtureEntry = Collection::make($manifest['artifacts'] ?? [])
            ->firstWhere('path', 'Web_Application/Academy-LMS/tests/Fixtures/community_fixture_set.json');
        self::assertNotNull($fixtureEntry, 'Manifest should include community fixture entry.');

        $expectedHash = hash('sha256', (string) file_get_contents($fixturePath));
        self::assertSame($expectedHash, $fixtureEntry['sha256']);
        self::assertSame(count($payload['communities']), $fixtureEntry['record_counts']['communities']);
        self::assertSame(
            array_sum(array_map(fn (array $community) => count($community['recent_posts']), $payload['communities'])),
            $fixtureEntry['record_counts']['posts']
        );
    }
}
