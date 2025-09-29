<?php

namespace App\Domain\Search\DataSources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class CommunityDataSource extends AbstractSearchDataSource
{
    protected function table(): string
    {
        return 'communities';
    }

    public function cursor(): LazyCollection
    {
        if (! Schema::hasTable($this->table())) {
            return LazyCollection::empty();
        }

        $query = DB::table('communities as c')
            ->select([
                'c.id',
                'c.slug',
                'c.name',
                'c.tagline',
                'c.bio',
                'c.about_html',
                'c.visibility',
                'c.join_policy',
                'c.is_featured',
                'c.links',
                'c.settings',
                'c.category_id',
                'c.geo_place_id',
                'c.launched_at',
                'c.created_at',
                'c.updated_at',
            ])
            ->orderBy('c.id');

        if (Schema::hasTable('community_categories')) {
            $query->addSelect('categories.name as category_name');
            $query->leftJoin('community_categories as categories', 'categories.id', '=', 'c.category_id');
        }

        if (Schema::hasTable('geo_places')) {
            $query->addSelect([
                'geo.name as geo_name',
                'geo.country_code as geo_country_code',
                'geo.metadata as geo_metadata',
                'geo.timezone as geo_timezone',
            ]);
            $query->leftJoin('geo_places as geo', 'geo.id', '=', 'c.geo_place_id');
        }

        if (Schema::hasTable('community_members')) {
            $memberStats = DB::table('community_members')
                ->selectRaw("community_id, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as member_count")
                ->selectRaw("SUM(CASE WHEN status = 'active' AND is_online = 1 THEN 1 ELSE 0 END) as online_count")
                ->groupBy('community_id');

            $query->leftJoinSub($memberStats, 'member_stats', function ($join) {
                $join->on('member_stats.community_id', '=', 'c.id');
            });

            $query->addSelect([
                DB::raw('COALESCE(member_stats.member_count, 0) as member_count'),
                DB::raw('COALESCE(member_stats.online_count, 0) as online_count'),
            ]);
        } else {
            $query->addSelect(DB::raw('0 as member_count'), DB::raw('0 as online_count'));
        }

        if (Schema::hasTable('community_posts')) {
            $recentActivity = DB::table('community_posts')
                ->selectRaw('community_id, MAX(COALESCE(published_at, updated_at, created_at)) as recent_activity_at')
                ->groupBy('community_id');

            $query->leftJoinSub($recentActivity, 'recent_activity', function ($join) {
                $join->on('recent_activity.community_id', '=', 'c.id');
            });

            $query->addSelect('recent_activity.recent_activity_at');
        } else {
            $query->addSelect(DB::raw('NULL as recent_activity_at'));
        }

        if (Schema::hasTable('community_subscription_tiers')) {
            $tierNames = DB::table('community_subscription_tiers')
                ->selectRaw("community_id, GROUP_CONCAT(name ORDER BY name SEPARATOR '||') as tier_names")
                ->groupBy('community_id');

            $query->leftJoinSub($tierNames, 'tier_stats', function ($join) {
                $join->on('tier_stats.community_id', '=', 'c.id');
            });

            $query->addSelect('tier_stats.tier_names');
        } else {
            $query->addSelect(DB::raw('NULL as tier_names'));
        }

        return $query
            ->lazy($this->chunkSize())
            ->map(function ($row) {
                return $this->transformer->fromArray((array) $row);
            })
            ->filter(fn (array $payload) => ! empty($payload));
    }
}
