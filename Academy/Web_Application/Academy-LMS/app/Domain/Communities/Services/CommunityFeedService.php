<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommunityFeedService
{
    public function fetchFeed(Community $community, string $filter = 'new', int $perPage = 25, ?string $cursor = null): CursorPaginator
    {
        $query = $this->baseFeedQuery($community, $filter);

        return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function fetchPinnedPosts(Community $community): Collection
    {
        return $community->posts()
            ->with(['author', 'paywallTier'])
            ->where('is_pinned', true)
            ->where('is_archived', false)
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();
    }

    public function publishDueScheduledPosts(): int
    {
        $now = CarbonImmutable::now();

        return CommunityPost::query()
            ->whereNull('published_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->update([
                'published_at' => $now,
                'is_archived' => false,
                'archived_at' => null,
            ]);
    }

    protected function baseFeedQuery(Community $community, string $filter): Builder
    {
        $query = $community->posts()
            ->with(['author', 'paywallTier'])
            ->whereNotNull('published_at')
            ->where('is_archived', false)
            ->where(function (Builder $builder) use ($community): void {
                $builder->where('visibility', '!=', 'paid')
                    ->orWhereNull('paywall_tier_id')
                    ->orWhereIn('paywall_tier_id', function ($sub) use ($community) {
                        $sub->select('subscription_tier_id')
                            ->from('community_subscriptions')
                            ->whereColumn('community_subscriptions.community_id', 'community_posts.community_id');
                    });
            });

        return match ($filter) {
            'top' => $query->orderByDesc('like_count')->orderByDesc('comment_count')->orderByDesc('published_at'),
            'trending' => $query->orderByDesc('comment_count')->orderByDesc('like_count')->orderByDesc('published_at'),
            'scheduled' => $community->posts()
                ->with(['author', 'paywallTier'])
                ->whereNull('published_at')
                ->whereNotNull('scheduled_at')
                ->orderBy('scheduled_at'),
            default => $query->orderByDesc('published_at'),
        };
    }
}

