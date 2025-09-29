<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\PostCreated;
use App\Events\Community\PostLiked;
use App\Jobs\Community\DistributeNotification;
use App\Jobs\Community\RebuildCommunityCounters;
use App\Jobs\Community\ReindexCommunitySearch;
use App\Jobs\Community\ScanCommunityMediaForMalware;
use App\Jobs\Community\TranscodeCommunityMedia;
use App\Models\Community\CommunityFollow;
use Illuminate\Support\Collection;

class DispatchPostCreatedNotifications
{
    public function handle(PostCreated|PostLiked $event): void
    {
        if ($event instanceof PostLiked) {
            $this->handleLike($event);

            return;
        }

        $this->handlePostCreated($event);
    }

    protected function handlePostCreated(PostCreated $event): void
    {
        $post = $event->post;
        $actorId = $event->member->user_id;

        $recipientIds = Collection::make(
            CommunityFollow::query()
                ->where('community_id', $post->community_id)
                ->where('notifications_enabled', true)
                ->pluck('follower_id')
        )
            ->merge($post->mentions ?? [])
            ->merge($post->metadata['notify_user_ids'] ?? [])
            ->push($post->community?->created_by)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $actorId)
            ->unique()
            ->values();

        if ($recipientIds->isNotEmpty()) {
            DistributeNotification::dispatch([
                'community_id' => $post->community_id,
                'event' => 'post.created',
                'recipient_ids' => $recipientIds->all(),
                'data' => [
                    'subject' => sprintf('%s posted in %s', $post->author?->name ?? 'A member', $post->community?->name ?? 'the community'),
                    'message' => $post->body_md ? str($post->body_md)->limit(140)->toString() : 'New activity in your community feed.',
                    'post_id' => $post->getKey(),
                    'actor_id' => $actorId,
                    'cta' => [
                        'label' => 'Open post',
                        'url' => url(sprintf('/communities/%s/posts/%s', $post->community_id, $post->getKey())),
                    ],
                ],
            ]);
        }

        if (! empty($post->media)) {
            ScanCommunityMediaForMalware::dispatch([
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
            ]);

            TranscodeCommunityMedia::dispatch([
                'community_id' => $post->community_id,
                'post_id' => $post->getKey(),
            ]);
        }

        ReindexCommunitySearch::dispatch([
            'model' => $post::class,
            'id' => $post->getKey(),
        ]);

        RebuildCommunityCounters::dispatch([
            'community_id' => $post->community_id,
        ]);
    }

    protected function handleLike(PostLiked $event): void
    {
        $post = $event->post;
        $actorId = $event->member->user_id;

        if (! $post->author_id || $post->author_id === $actorId) {
            return;
        }

        DistributeNotification::dispatch([
            'community_id' => $post->community_id,
            'event' => 'post.liked',
            'recipient_ids' => [$post->author_id],
            'data' => [
                'subject' => 'Your post received new feedback',
                'message' => sprintf('%s reacted with %s', $event->member->user?->name ?? 'A member', $event->like->reaction),
                'post_id' => $post->getKey(),
                'actor_id' => $actorId,
            ],
        ]);
    }
}
