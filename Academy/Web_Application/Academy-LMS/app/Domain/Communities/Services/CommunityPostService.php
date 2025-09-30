<?php

namespace App\Domain\Communities\Services;

use App\Domain\Analytics\Services\AnalyticsDispatcher;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Events\Community\PostCreated;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityPostService
{
    public function __construct(private readonly AnalyticsDispatcher $analytics)
    {
    }

    public function compose(Community $community, User $author, array $payload): CommunityPost
    {
        return DB::transaction(function () use ($community, $author, $payload) {
            $post = new CommunityPost();
            $post->community()->associate($community);
            $post->author()->associate($author);
            $post->type = $payload['type'] ?? 'text';
            $post->body_md = $payload['body_md'] ?? null;
            $post->body_html = $payload['body_html'] ?? null;
            $post->media = $payload['media'] ?? null;
            $post->visibility = $payload['visibility'] ?? 'community';
            $post->metadata = $payload['metadata'] ?? [];
            $post->mentions = $payload['mentions'] ?? [];
            $post->topics = $payload['topics'] ?? [];
            $post->is_pinned = (bool)($payload['is_pinned'] ?? false);
            $post->is_locked = (bool)($payload['is_locked'] ?? false);

            if (!empty($payload['paywall_tier_id'])) {
                $tier = CommunitySubscriptionTier::query()
                    ->where('community_id', $community->getKey())
                    ->findOrFail($payload['paywall_tier_id']);
                $post->paywallTier()->associate($tier);
            }

            $scheduleAt = isset($payload['scheduled_at']) ? CarbonImmutable::parse($payload['scheduled_at']) : null;
            $post->scheduled_at = $scheduleAt;
            $post->published_at = $scheduleAt && $scheduleAt->greaterThan(CarbonImmutable::now())
                ? null
                : CarbonImmutable::now();

            $post->save();

            Log::info('community.posts.composed', [
                'community_id' => $community->getKey(),
                'post_id' => $post->getKey(),
                'author_id' => $author->getKey(),
                'visibility' => $post->visibility,
            ]);

            $membership = CommunityMember::query()
                ->where('community_id', $community->getKey())
                ->where('user_id', $author->getKey())
                ->first();

            if ($membership) {
                $freshPost = $post->fresh(['author', 'community']);
                event(new PostCreated($freshPost, $membership));

                $this->analytics->record('post_create', $author, [
                    'community_id' => $community->getKey(),
                    'post_id' => $freshPost->getKey(),
                    'visibility' => $freshPost->visibility,
                    'media_types' => $this->resolveMediaTypes($freshPost->media ?? []),
                    'scheduled' => (bool) $freshPost->scheduled_at,
                ], $community);
            }

            return $post->fresh(['author', 'community']);
        });
    }

    public function update(CommunityPost $post, array $payload): CommunityPost
    {
        return DB::transaction(function () use ($post, $payload) {
            $post->fill([
                'body_md' => $payload['body_md'] ?? $post->body_md,
                'body_html' => $payload['body_html'] ?? $post->body_html,
                'media' => $payload['media'] ?? $post->media,
                'mentions' => $payload['mentions'] ?? $post->mentions,
                'topics' => $payload['topics'] ?? $post->topics,
                'visibility' => $payload['visibility'] ?? $post->visibility,
                'metadata' => $payload['metadata'] ?? $post->metadata,
                'is_locked' => $payload['is_locked'] ?? $post->is_locked,
                'is_pinned' => $payload['is_pinned'] ?? $post->is_pinned,
            ]);

            if (array_key_exists('paywall_tier_id', $payload)) {
                $tierId = $payload['paywall_tier_id'];
                if ($tierId) {
                    $tier = CommunitySubscriptionTier::query()
                        ->where('community_id', $post->community_id)
                        ->findOrFail($tierId);
                    $post->paywallTier()->associate($tier);
                } else {
                    $post->paywallTier()->dissociate();
                }
            }

            if (array_key_exists('scheduled_at', $payload)) {
                $scheduleAt = $payload['scheduled_at'] ? CarbonImmutable::parse($payload['scheduled_at']) : null;
                $post->scheduled_at = $scheduleAt;
            }

            if (array_key_exists('published_at', $payload)) {
                $post->published_at = $payload['published_at'] ? CarbonImmutable::parse($payload['published_at']) : null;
            }

            $post->save();

            Log::info('community.posts.updated', [
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
            ]);

            $this->analytics->record('post_update', $post->author, [
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
                'changes' => array_keys($payload),
            ], $post->community);

            return $post->fresh(['author', 'paywallTier']);
        });
    }

    public function destroy(CommunityPost $post, User $actor): void
    {
        DB::transaction(function () use ($post, $actor): void {
            $post->delete();

            $membership = CommunityMember::query()
                ->where('community_id', $post->community_id)
                ->where('user_id', $actor->getKey())
                ->first();

            if ($membership) {
                $membership->points = max(0, $membership->points - 5);
                $membership->save();
            }

            Log::warning('community.posts.deleted', [
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
                'actor_id' => $actor->getKey(),
            ]);

            $this->analytics->record('post_delete', $actor, [
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
            ], $post->community);
        });
    }

    /**
     * @param array<int, mixed> $media
     * @return array<int, string>
     */
    private function resolveMediaTypes(array $media): array
    {
        return collect($media)
            ->map(function ($item) {
                if (is_array($item) && isset($item['type'])) {
                    return (string) $item['type'];
                }

                if (is_string($item)) {
                    return str_contains($item, '.mp4') ? 'video' : 'image';
                }

                return 'file';
            })
            ->values()
            ->all();
    }
}

