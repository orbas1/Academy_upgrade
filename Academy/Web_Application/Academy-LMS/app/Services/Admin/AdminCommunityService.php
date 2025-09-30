<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostLike;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminCommunityService
{
    public function __construct(private readonly CommonMarkConverter $markdown)
    {
    }

    public function summarizeCommunities(array $filters = [], int $perPage = 25, ?string $cursor = null): array
    {
        $query = $this->applySummaryFilters($filters);
        $total = (clone $query)->count('communities.id');

        /** @var CursorPaginator $paginator */
        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
        $paginator->setCollection($paginator->getCollection()->map(fn (Community $community) => $this->mapSummary($community)));

        return [
            'paginator' => $paginator,
            'total' => $total,
        ];
    }

    public function findCommunityById(int $communityId): Community
    {
        return Community::query()
            ->with(['category', 'geoPlace'])
            ->findOrFail($communityId);
    }

    public function showCommunity(Community $community): array
    {
        $community->loadMissing(['category:id,name', 'geoPlace:id,name,country_code,timezone']);

        $owners = $community->members()
            ->whereIn('role', ['owner', 'admin'])
            ->where('status', 'active')
            ->with('user:id,name,email,profile_photo_path,photo,role')
            ->orderBy('role')
            ->orderBy('joined_at')
            ->get()
            ->map(fn (CommunityMember $member) => [
                'id' => (int) $member->user_id,
                'name' => $member->user?->name ?? 'Unknown member',
                'avatar_url' => $this->avatarUrl($member->user),
            ])
            ->values()
            ->all();

        $summary = $this->mapSummary($community);

        return array_merge($summary, [
            'description' => $community->bio ?? null,
            'category' => $community->category?->name,
            'created_at' => optional($community->created_at)->toIso8601String(),
            'owners' => $owners,
        ]);
    }

    public function loadMembers(Community $community, array $filters = [], int $perPage = 25, ?string $cursor = null): array
    {
        $query = $community->members()
            ->with('user:id,name,email,profile_photo_path,photo,role')
            ->orderByDesc('joined_at')
            ->orderBy('user_id');

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('online', $filters)) {
            $query->where('is_online', (bool) $filters['online']);
        }

        if (! empty($filters['joined_after'])) {
            $query->where('joined_at', '>=', CarbonImmutable::parse($filters['joined_after']));
        }

        if (! empty($filters['joined_before'])) {
            $query->where('joined_at', '<=', CarbonImmutable::parse($filters['joined_before']));
        }

        $total = (clone $query)->count('community_members.id');
        /** @var CursorPaginator $paginator */
        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $paginator->setCollection($paginator->getCollection()->map(function (CommunityMember $member) {
            return [
                'id' => (int) $member->user_id,
                'name' => $member->user?->name ?? 'Unknown member',
                'email' => $member->user?->email,
                'avatar_url' => $this->avatarUrl($member->user),
                'role' => $member->role,
                'status' => $member->status,
                'joined_at' => optional($member->joined_at)->toIso8601String(),
                'last_active_at' => optional($member->last_seen_at)->toIso8601String(),
            ];
        }));

        return [
            'paginator' => $paginator,
            'total' => $total,
        ];
    }

    public function loadMetrics(Community $community): array
    {
        $now = CarbonImmutable::now();
        $members = $community->members()->where('status', 'active');

        $dau = (clone $members)->where('last_seen_at', '>=', $now->subDay())->count();
        $wau = (clone $members)->where('last_seen_at', '>=', $now->subDays(7))->count();
        $mau = (clone $members)->where('last_seen_at', '>=', $now->subDays(30))->count();

        $previousWeek = (clone $members)
            ->whereBetween('last_seen_at', [$now->subDays(14), $now->subDays(7)])
            ->count();
        $previousMonth = (clone $members)
            ->whereBetween('last_seen_at', [$now->subDays(60), $now->subDays(30)])
            ->count();

        $retention7 = $this->ratio($dau, $previousWeek);
        $retention28 = $this->ratio($wau, $previousMonth);
        $retention90 = $this->ratio(
            (clone $members)->where('last_seen_at', '>=', $now->subDays(90))->count(),
            (clone $members)->whereBetween('last_seen_at', [$now->subDays(180), $now->subDays(90)])->count()
        );

        $firstPostMembers = CommunityPost::query()
            ->select('author_id')
            ->where('community_id', $community->getKey())
            ->distinct()
            ->count('author_id');
        $activeMembers = (clone $members)->count();
        $conversionFirstPost = $this->ratio($firstPostMembers, $activeMembers);

        $monetisedSubscriptions = CommunitySubscription::query()
            ->selectRaw('COALESCE(SUM(tiers.price_cents), 0) as total')
            ->join('community_subscription_tiers as tiers', 'tiers.id', '=', 'community_subscriptions.subscription_tier_id')
            ->where('community_subscriptions.community_id', $community->getKey())
            ->whereIn('community_subscriptions.status', ['active', 'trialing'])
            ->value('total');
        $mrr = ($monetisedSubscriptions ?? 0) / 100;

        $churned = CommunitySubscription::query()
            ->where('community_id', $community->getKey())
            ->whereIn('status', ['canceled', 'expired', 'past_due'])
            ->where('updated_at', '>=', $now->subDays(30))
            ->count();
        $churnRate = $this->ratio($churned, max($activeMembers, 1));

        $arpu = $activeMembers > 0 ? $mrr / $activeMembers : 0.0;
        $ltv = $churnRate > 0 ? $arpu / $churnRate : $arpu * 12;

        $postsLastHour = $community->posts()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $now->subHour())
            ->count();
        $postsPerMinute = $postsLastHour / 60;

        $queueSize = CommunityPost::query()
            ->where('community_id', $community->getKey())
            ->whereRaw("JSON_EXTRACT(COALESCE(metadata, '{}'), '$.moderation.status') = '" . json_encode('pending') . "'")
            ->count();

        return [
            'dau' => $dau,
            'wau' => $wau,
            'mau' => $mau,
            'retention_7' => $retention7,
            'retention_28' => $retention28,
            'retention_90' => $retention90,
            'conversion_first_post' => $conversionFirstPost,
            'mrr' => round($mrr, 2),
            'churn_rate' => $churnRate,
            'arpu' => round($arpu, 2),
            'ltv' => round($ltv, 2),
            'posts_per_minute' => round($postsPerMinute, 2),
            'queue_size' => $queueSize,
        ];
    }

    public function loadFeed(
        Community $community,
        User $viewer,
        string $filter = 'new',
        int $perPage = 25,
        ?string $cursor = null
    ): array {
        $query = $community->posts()
            ->with('author:id,name,email,profile_photo_path,photo,role')
            ->whereNotNull('published_at');

        $query = match ($filter) {
            'top' => $query->orderByDesc('like_count')->orderByDesc('comment_count'),
            'moderation' => $query
                ->whereRaw("JSON_EXTRACT(COALESCE(metadata, '{}'), '$.moderation.status') = '" . json_encode('pending') . "'")
                ->orderByDesc('created_at'),
            default => $query->orderByDesc('published_at'),
        };

        $total = (clone $query)->count('community_posts.id');
        /** @var CursorPaginator $paginator */
        $paginator = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $posts = $paginator->getCollection();
        $postIds = $posts->pluck('id')->all();

        $reactions = $postIds === []
            ? collect()
            : CommunityPostLike::query()
                ->select('post_id', 'reaction', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('post_id', $postIds)
                ->groupBy('post_id', 'reaction')
                ->get()
                ->groupBy('post_id');

        $viewerReactions = $postIds === []
            ? collect()
            : CommunityPostLike::query()
                ->select('post_id', 'reaction')
                ->whereIn('post_id', $postIds)
                ->where('user_id', $viewer->getKey())
                ->get()
                ->keyBy('post_id');

        $paginator->setCollection($posts->map(function (CommunityPost $post) use ($reactions, $viewerReactions) {
            $media = $this->normalizeMedia($post->media ?? []);

            return [
                'id' => (int) $post->getKey(),
                'author' => [
                    'id' => (int) $post->author_id,
                    'name' => $post->author?->name ?? 'Unknown member',
                    'avatar_url' => $this->avatarUrl($post->author),
                    'role' => $post->author?->role,
                ],
                'created_at' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
                'visibility' => $post->visibility,
                'body' => $post->body_md ?? '',
                'body_html' => $post->body_html ?? $this->markdown->convertToHtml($post->body_md ?? ''),
                'like_count' => (int) $post->like_count,
                'comment_count' => (int) $post->comment_count,
                'viewer_reaction' => $viewerReactions->get($post->getKey())?->reaction,
                'reaction_breakdown' => $reactions->get($post->getKey())
                    ?->mapWithKeys(fn ($entry) => [$entry->reaction => (int) $entry->aggregate])
                    ?->toArray() ?? [],
                'attachments' => $media,
                'paywall_tier_id' => $post->paywall_tier_id ? (int) $post->paywall_tier_id : null,
                'is_archived' => (bool) $post->is_archived,
                'archived_at' => $post->archived_at?->toIso8601String(),
            ];
        }));

        return [
            'paginator' => $paginator,
            'total' => $total,
        ];
    }

    public function createPost(Community $community, User $author, array $payload): array
    {
        $body = trim((string) ($payload['body'] ?? ''));
        if ($body === '') {
            throw new HttpException(422, 'Post body is required.');
        }

        $visibility = in_array($payload['visibility'] ?? 'community', ['community', 'public', 'paid'], true)
            ? $payload['visibility']
            : 'community';

        $scheduledAt = ! empty($payload['scheduled_at'])
            ? CarbonImmutable::parse($payload['scheduled_at'])
            : null;

        $media = $this->ingestMedia($payload['attachments'] ?? []);

        $post = new CommunityPost();
        $post->community()->associate($community);
        $post->author()->associate($author);
        $post->type = $media === [] ? 'text' : $this->inferTypeFromMedia($media);
        $post->body_md = $body;
        $post->body_html = $this->markdown->convertToHtml($body);
        $post->visibility = $visibility;
        $post->paywall_tier_id = $payload['paywall_tier_id'] ?? null;
        $post->media = $media;
        $post->scheduled_at = $scheduledAt;
        $post->published_at = $scheduledAt ? null : CarbonImmutable::now();
        $post->save();

        $post->load('author');

        $envelope = $this->loadFeed($community, $author, 'new', 1);
        $created = collect($envelope['paginator']->items())
            ->firstWhere('id', (int) $post->getKey());

        if (is_array($created)) {
            return $created;
        }

        return [
            'id' => (int) $post->getKey(),
            'author' => [
                'id' => (int) $author->getKey(),
                'name' => $author->name,
                'avatar_url' => $this->avatarUrl($author),
                'role' => $author->role,
            ],
            'created_at' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
            'visibility' => $post->visibility,
            'body' => $post->body_md ?? '',
            'body_html' => $post->body_html ?? $this->markdown->convertToHtml($post->body_md ?? ''),
            'like_count' => 0,
            'comment_count' => 0,
            'viewer_reaction' => null,
            'reaction_breakdown' => [],
            'attachments' => $this->normalizeMedia($post->media ?? []),
            'paywall_tier_id' => $post->paywall_tier_id ? (int) $post->paywall_tier_id : null,
        ];
    }

    public function toggleReaction(CommunityPost $post, User $user, ?string $reaction): array
    {
        $normalized = $reaction && in_array($reaction, ['like', 'love', 'insightful', 'celebrate'], true)
            ? $reaction
            : null;

        $existing = CommunityPostLike::query()
            ->where('post_id', $post->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($normalized === null) {
            if ($existing) {
                $existing->delete();
            }
        } else {
            if ($existing) {
                $existing->reaction = $normalized;
                $existing->reacted_at = CarbonImmutable::now();
                $existing->save();
            } else {
                CommunityPostLike::create([
                    'post_id' => $post->getKey(),
                    'user_id' => $user->getKey(),
                    'reaction' => $normalized,
                    'reacted_at' => CarbonImmutable::now(),
                ]);
            }
        }

        $post->like_count = CommunityPostLike::query()->where('post_id', $post->getKey())->count();
        $post->save();

        $post->refresh()->load('author');

        $envelope = $this->loadFeed($post->community, $user, 'new', 1);
        $mapped = collect($envelope['paginator']->items())
            ->firstWhere('id', (int) $post->getKey());

        if (is_array($mapped)) {
            return $mapped;
        }

        return [
            'id' => (int) $post->getKey(),
            'author' => [
                'id' => (int) $post->author_id,
                'name' => $post->author?->name ?? 'Unknown member',
                'avatar_url' => $this->avatarUrl($post->author),
                'role' => $post->author?->role,
            ],
            'created_at' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
            'visibility' => $post->visibility,
            'body' => $post->body_md ?? '',
            'body_html' => $post->body_html ?? $this->markdown->convertToHtml($post->body_md ?? ''),
            'like_count' => (int) $post->like_count,
            'comment_count' => (int) $post->comment_count,
            'viewer_reaction' => $reaction,
            'reaction_breakdown' => [],
            'attachments' => $this->normalizeMedia($post->media ?? []),
            'paywall_tier_id' => $post->paywall_tier_id ? (int) $post->paywall_tier_id : null,
        ];
    }

    private function applySummaryFilters(array $filters): Builder
    {
        $sinceDay = CarbonImmutable::now()->subDay();

        $query = Community::query()
            ->withCount([
                'members as members_count' => fn (Builder $builder) => $builder->where('status', 'active'),
                'members as online_count' => fn (Builder $builder) => $builder
                    ->where('status', 'active')
                    ->where('is_online', true),
                'posts as posts_last_24h' => fn (Builder $builder) => $builder
                    ->where('is_archived', false)
                    ->whereNotNull('published_at')
                    ->where('published_at', '>=', $sinceDay),
            ])
            ->select('communities.*')
            ->selectSub(
                CommunityPost::query()
                    ->selectRaw('COALESCE(SUM(comment_count), 0)')
                    ->whereColumn('community_id', 'communities.id')
                    ->where('is_archived', false)
                    ->whereNotNull('published_at')
                    ->where('published_at', '>=', $sinceDay),
                'comments_last_24h'
            )
            ->selectSub(
                CommunityPost::query()
                    ->selectRaw('MAX(COALESCE(published_at, updated_at, created_at))')
                    ->whereColumn('community_id', 'communities.id'),
                'last_activity_at'
            )
            ->selectSub(
                CommunityPost::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('community_id', 'communities.id')
                    ->whereRaw("JSON_EXTRACT(COALESCE(metadata, '{}'), '$.moderation.status') = '" . json_encode('flagged') . "'"),
                'open_flags'
            )
            ->selectSub(
                CommunitySubscriptionTier::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('community_id', 'communities.id')
                    ->whereNull('deleted_at'),
                'tier_count'
            )
            ->selectSub(
                CommunitySubscription::query()
                    ->selectRaw('COALESCE(SUM(tiers.price_cents), 0)')
                    ->join('community_subscription_tiers as tiers', 'tiers.id', '=', 'community_subscriptions.subscription_tier_id')
                    ->whereColumn('community_subscriptions.community_id', 'communities.id')
                    ->whereIn('community_subscriptions.status', ['active', 'trialing']),
                'mrr_cents'
            )
            ->orderByDesc('communities.created_at')
            ->orderByDesc('communities.id');

        if (! empty($filters['visibility']) && $filters['visibility'] !== 'all') {
            $query->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['paywall'])) {
            if ($filters['paywall'] === 'enabled') {
                $query->whereExists(function (Builder $builder) {
                    $builder->selectRaw('1')
                        ->from('community_subscription_tiers as cst')
                        ->whereColumn('cst.community_id', 'communities.id')
                        ->whereNull('cst.deleted_at');
                });
            } elseif ($filters['paywall'] === 'disabled') {
                $query->whereDoesntHave('subscriptionTiers', function (Builder $builder) {
                    $builder->whereNull('deleted_at');
                });
            }
        }

        if (! empty($filters['search'])) {
            $term = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('communities.name', 'like', $term)
                    ->orWhere('communities.slug', 'like', $term)
                    ->orWhereExists(function (Builder $sub) use ($term) {
                        $sub->selectRaw('1')
                            ->from('community_members as cm')
                            ->join('users as owner_users', 'owner_users.id', '=', 'cm.user_id')
                            ->whereColumn('cm.community_id', 'communities.id')
                            ->whereIn('cm.role', ['owner', 'admin'])
                            ->where('owner_users.name', 'like', $term);
                    });
            });
        }

        return $query;
    }

    private function mapSummary(Community $community): array
    {
        $lastActivity = $community->getAttribute('last_activity_at');
        if ($lastActivity && ! $lastActivity instanceof CarbonImmutable) {
            $lastActivity = CarbonImmutable::parse($lastActivity);
        }

        $comments = (int) ($community->getAttribute('comments_last_24h') ?? 0);
        $mrrCents = (int) ($community->getAttribute('mrr_cents') ?? 0);

        return [
            'id' => (int) $community->getKey(),
            'name' => $community->name,
            'slug' => $community->slug,
            'visibility' => $community->visibility,
            'tagline' => $community->tagline,
            'member_count' => (int) $community->getAttribute('members_count'),
            'online_now' => (int) $community->getAttribute('online_count'),
            'posts_per_day' => (int) $community->getAttribute('posts_last_24h'),
            'comments_per_day' => $comments,
            'paywall_enabled' => (int) ($community->getAttribute('tier_count') ?? 0) > 0,
            'mrr' => round($mrrCents / 100, 2),
            'open_flags' => (int) $community->getAttribute('open_flags'),
            'last_activity_at' => $lastActivity?->toIso8601String(),
        ];
    }

    private function ratio(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round($numerator / max($denominator, 1), 4);
    }

    private function avatarUrl(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if (filled($user->profile_photo_path ?? null)) {
            return Storage::disk(config('filesystems.default'))->url($user->profile_photo_path);
        }

        if (filled($user->photo ?? null)) {
            return Storage::disk(config('filesystems.default'))->url($user->photo);
        }

        return null;
    }

    private function normalizeMedia(mixed $media): array
    {
        if (is_string($media) && $media !== '') {
            $decoded = json_decode($media, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $media = $decoded;
            }
        }

        if (! is_array($media)) {
            return [];
        }

        return collect($media)
            ->filter(fn ($item) => is_array($item) && isset($item['url']))
            ->map(function (array $item) {
                return [
                    'id' => $item['id'] ?? null,
                    'type' => $item['type'] ?? 'link',
                    'url' => $item['url'],
                    'thumbnail_url' => $item['thumbnail_url'] ?? null,
                    'mime_type' => $item['mime_type'] ?? null,
                    'title' => $item['title'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function ingestMedia(array $files): array
    {
        if ($files === []) {
            return [];
        }

        return collect($files)
            ->filter(fn ($file) => $file instanceof \Illuminate\Http\UploadedFile)
            ->map(function (\Illuminate\Http\UploadedFile $file) {
                $path = $file->store('community/posts', ['disk' => config('filesystems.default')]);
                $mime = $file->getMimeType();

                return [
                    'id' => null,
                    'type' => $this->mapMimeToType($mime),
                    'url' => Storage::disk(config('filesystems.default'))->url($path),
                    'thumbnail_url' => null,
                    'mime_type' => $mime,
                    'title' => $file->getClientOriginalName(),
                    'description' => null,
                ];
            })
            ->values()
            ->all();
    }

    private function mapMimeToType(?string $mime): string
    {
        if (! $mime) {
            return 'link';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_contains($mime, 'pdf') || str_contains($mime, 'sheet') || str_contains($mime, 'msword')) {
            return 'document';
        }

        return 'link';
    }

    private function inferTypeFromMedia(array $media): string
    {
        $primary = $media[0]['type'] ?? null;
        if (is_string($primary) && $primary !== '') {
            return $primary;
        }

        return 'text';
    }
}
