<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostLike;
use App\Domain\Communities\Services\CommunityFeedService;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EloquentFeedService implements FeedService
{
    public function __construct(private readonly CommunityFeedService $feedService)
    {
    }

    public function getCommunityFeed(
        Community $community,
        ?CommunityMember $member,
        string $filter,
        int $perPage = 20,
        ?string $cursor = null
    ): CursorPaginator {
        $normalizedFilter = $this->normalizeFilter($filter);

        $paginator = $this->feedService->fetchFeed($community, $normalizedFilter, $perPage, $cursor);

        return $this->hydrateFeed($paginator, $community, $member);
    }

    public function getPinnedPosts(Community $community): Collection
    {
        $posts = $this->feedService->fetchPinnedPosts($community);

        return $posts->map(fn (CommunityPost $post) => $this->mapPost($post, null, $community));
    }

    public function getMediaFeed(
        Community $community,
        ?CommunityMember $member,
        int $perPage = 20,
        ?string $cursor = null
    ): CursorPaginator {
        $paginator = $this->feedService->fetchFeed($community, 'top', $perPage, $cursor);

        $paginator->setCollection(
            $paginator->getCollection()->filter(function (CommunityPost $post) {
                $media = $post->media ?? [];

                return is_array($media) && $media !== [];
            })
        );

        return $this->hydrateFeed($paginator, $community, $member);
    }

    public function getPostWithContext(
        Community $community,
        CommunityPost $post,
        ?CommunityMember $member = null
    ): array {
        if ((int) $post->community_id !== (int) $community->getKey()) {
            throw new \InvalidArgumentException('Post does not belong to the provided community.');
        }

        return [
            'community' => [
                'id' => (int) $community->getKey(),
                'slug' => $community->slug,
                'name' => $community->name,
            ],
            'post' => $this->mapPost($post->loadMissing(['author', 'paywallTier']), $member, $community),
        ];
    }

    private function hydrateFeed(CursorPaginator $paginator, Community $community, ?CommunityMember $member): CursorPaginator
    {
        $collection = $paginator->getCollection();
        $postIds = $collection->pluck('id')->all();
        $viewerLikes = [];

        if ($member && $postIds !== []) {
            $viewerLikes = CommunityPostLike::query()
                ->whereIn('post_id', $postIds)
                ->where('user_id', $member->user_id)
                ->pluck('reaction', 'post_id')
                ->map(fn (?string $reaction) => $reaction !== null)
                ->toArray();
        }

        $mapped = $collection->map(fn (CommunityPost $post) => $this->mapPost($post, $member, $community, $viewerLikes[$post->id] ?? false));

        $paginator->setCollection($mapped);

        return $paginator;
    }

    private function mapPost(
        CommunityPost $post,
        ?CommunityMember $member,
        Community $community,
        bool $viewerLiked = false
    ): array {
        $post->loadMissing(['author', 'paywallTier']);
        $createdAt = $post->published_at ?? $post->created_at;
        $bodyMarkdown = $post->body_md ?? '';
        $bodyHtml = $post->body_html ?? '';
        $body = $bodyMarkdown !== '' ? $bodyMarkdown : Str::of($bodyHtml)->stripTags()->toString();

        return [
            'id' => (int) $post->getKey(),
            'community_id' => (int) $community->getKey(),
            'type' => $post->type,
            'author_name' => $post->author?->name ?? 'Unknown member',
            'author_id' => (int) ($post->author_id ?? 0),
            'body' => $body,
            'body_md' => $bodyMarkdown,
            'body_html' => $bodyHtml,
            'created_at' => $createdAt?->toIso8601String(),
            'like_count' => (int) $post->like_count,
            'comment_count' => (int) $post->comment_count,
            'visibility' => $post->visibility,
            'liked' => $viewerLiked,
            'paywall_tier_id' => $post->paywall_tier_id ? (int) $post->paywall_tier_id : null,
            'is_archived' => (bool) $post->is_archived,
            'archived_at' => $post->archived_at?->toIso8601String(),
            'attachments' => $this->normalizeMedia($post->media),
            'share_url' => $post->metadata['permalink'] ?? null,
        ];
    }

    private function normalizeFilter(string $filter): string
    {
        $allowed = ['new', 'top', 'trending', 'scheduled'];

        return in_array($filter, $allowed, true) ? $filter : 'new';
    }

    private function normalizeMedia(mixed $media): array
    {
        if (is_string($media) && $media !== '') {
            $decoded = json_decode($media, true);
            $media = json_last_error() === JSON_ERROR_NONE ? $decoded : $media;
        }

        if (! is_array($media)) {
            return [];
        }

        return collect($media)
            ->filter(fn ($item) => is_array($item) && isset($item['url']))
            ->map(fn (array $item) => [
                'type' => $item['type'] ?? 'link',
                'url' => $item['url'],
                'thumbnail_url' => $item['thumbnail_url'] ?? null,
                'mime_type' => $item['mime_type'] ?? null,
                'title' => $item['title'] ?? null,
                'description' => $item['description'] ?? null,
            ])
            ->values()
            ->all();
    }
}
