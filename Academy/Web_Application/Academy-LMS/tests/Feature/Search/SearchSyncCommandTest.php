<?php

namespace Tests\Feature\Search;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchSyncCommandTest extends TestCase
{

    public function test_it_synchronises_configured_indexes(): void
    {
        Config::set('search.meilisearch', [
            'host' => 'http://meili.test',
            'key' => 'test-key',
            'timeout' => 5,
            'indexes' => [
                'communities' => [
                    'primaryKey' => 'id',
                    'rankingRules' => ['words'],
                    'synonyms' => [
                        'community' => ['group'],
                    ],
                ],
            ],
        ]);

        Http::fake([
            'http://meili.test/*' => Http::response([], 202),
        ]);

        $exitCode = Artisan::call('search:sync');

        $this->assertSame(0, $exitCode);

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && $request->url() === 'http://meili.test/indexes/communities'
                && $request->method() === 'PUT'
                && $request['primaryKey'] === 'id';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://meili.test/indexes/communities/settings'
                && $request->method() === 'PATCH'
                && $request['rankingRules'] === ['words']
                && $request['synonyms'] === ['community' => ['group']];
        });
    }

    public function test_it_can_target_a_single_index(): void
    {
        Config::set('search.meilisearch.indexes', [
            'communities' => [
                'primaryKey' => 'id',
                'rankingRules' => ['words'],
            ],
            'posts' => [
                'primaryKey' => 'id',
                'rankingRules' => ['typo'],
            ],
        ]);

        Http::fake([
            '*' => Http::response([], 202),
        ]);

        $exitCode = Artisan::call('search:sync', ['--index' => 'posts']);

        $this->assertSame(0, $exitCode);

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'indexes/posts');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'indexes/posts/settings')
                && $request['rankingRules'] === ['typo'];
        });
    }
}
