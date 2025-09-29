<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\CommentCreated;
use App\Jobs\Community\DistributeNotification;
use App\Jobs\Community\RebuildCommunityCounters;
use App\Jobs\Community\ReindexCommunitySearch;
use App\Models\Community\CommunityPostComment;
use Illuminate\Support\Collection;

class DispatchCommentCreatedNotifications
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $post = $event->post;
        $actorId = $event->member->user_id;

        $parentAuthorId = $comment->parent_id
            ? CommunityPostComment::query()->whereKey($comment->parent_id)->value('author_id')
            : null;

        $recipientIds = Collection::make([$post->author_id, $parentAuthorId])
            ->merge($comment->mentions ?? [])
            ->merge($post->mentions ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $actorId)
            ->unique()
            ->values();

        if ($recipientIds->isNotEmpty()) {
            DistributeNotification::dispatch([
                'community_id' => $post->community_id,
                'event' => 'comment.created',
                'recipient_ids' => $recipientIds->all(),
                'data' => [
                    'subject' => 'New comment in a thread you follow',
                    'message' => str($comment->body_md)->limit(140)->toString(),
                    'post_id' => $post->getKey(),
                    'comment_id' => $comment->getKey(),
                    'actor_id' => $actorId,
                ],
            ]);
        }

        ReindexCommunitySearch::dispatch([
            'model' => CommunityPostComment::class,
            'id' => $comment->getKey(),
        ]);

        RebuildCommunityCounters::dispatch([
            'community_id' => $post->community_id,
        ]);
    }
}
