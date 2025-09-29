<?php

namespace App\Domain\Search\DataSources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class PostDataSource extends AbstractSearchDataSource
{
    protected function table(): string
    {
        return 'community_posts';
    }

    public function cursor(): LazyCollection
    {
        if (! Schema::hasTable($this->table())) {
            return LazyCollection::empty();
        }

        $query = DB::table('community_posts as posts')
            ->select([
                'posts.id',
                'posts.community_id',
                'posts.author_id',
                'posts.type',
                'posts.body_md',
                'posts.body_html',
                'posts.media',
                'posts.visibility',
                'posts.paywall_tier_id',
                'posts.like_count',
                'posts.comment_count',
                'posts.share_count',
                'posts.view_count',
                'posts.scheduled_at',
                'posts.published_at',
                'posts.expires_at',
                'posts.mentions',
                'posts.topics',
                'posts.metadata',
                'posts.created_at',
                'posts.updated_at',
            ])
            ->orderBy('posts.id');

        if (Schema::hasTable('users')) {
            $query->addSelect('users.name as author_name');
            $query->leftJoin('users', 'users.id', '=', 'posts.author_id');
        }

        if (Schema::hasTable('communities')) {
            $query->addSelect('communities.slug as community_slug');
            $query->leftJoin('communities', 'communities.id', '=', 'posts.community_id');
        }

        $query->addSelect(DB::raw('COALESCE(posts.like_count * 1.5 + posts.comment_count * 2 + posts.share_count * 1.25 + posts.view_count * 0.1, 0) as engagement_score'));

        return $query
            ->lazy($this->chunkSize())
            ->map(function ($row) {
                return $this->transformer->fromArray((array) $row);
            })
            ->filter(fn (array $payload) => ! empty($payload));
    }
}
