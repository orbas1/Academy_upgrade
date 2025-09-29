<?php

namespace App\Domain\Search\DataSources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class CommentDataSource extends AbstractSearchDataSource
{
    protected function table(): string
    {
        return 'community_post_comments';
    }

    public function cursor(): LazyCollection
    {
        if (! Schema::hasTable($this->table())) {
            return LazyCollection::empty();
        }

        $query = DB::table('community_post_comments as comments')
            ->select([
                'comments.id',
                'comments.community_id',
                'comments.post_id',
                'comments.author_id',
                'comments.body_md',
                'comments.body_html',
                'comments.mentions',
                'comments.like_count',
                'comments.reply_count',
                'comments.created_at',
                'comments.updated_at',
            ])
            ->orderBy('comments.id');

        if (Schema::hasTable('users')) {
            $query->addSelect('users.name as author_name');
            $query->leftJoin('users', 'users.id', '=', 'comments.author_id');
        }

        if (Schema::hasTable('community_posts')) {
            $query->addSelect([
                'posts.visibility as post_visibility',
                'posts.paywall_tier_id',
                'posts.metadata as post_metadata',
                'posts.body_md as post_body_md',
                'posts.body_html as post_body_html',
                'posts.community_id as post_community_id',
            ]);
            $query->leftJoin('community_posts as posts', 'posts.id', '=', 'comments.post_id');
        }

        if (Schema::hasTable('communities')) {
            $query->addSelect('communities.slug as community_slug');
            $query->leftJoin('communities', 'communities.id', '=', 'comments.community_id');
        }

        return $query
            ->lazy($this->chunkSize())
            ->map(function ($row) {
                return $this->transformer->fromArray((array) $row);
            })
            ->filter(fn (array $payload) => ! empty($payload));
    }
}
