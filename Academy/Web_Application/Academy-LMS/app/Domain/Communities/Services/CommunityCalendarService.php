<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CommunityCalendarService
{
    public function __construct(private readonly CommunityPostService $postService)
    {
    }

    public function scheduleEvent(Community $community, User $author, array $payload): CommunityPost
    {
        $eventMetadata = [
            'event_start_at' => CarbonImmutable::parse($payload['event_start_at'])->toIso8601String(),
            'event_end_at' => isset($payload['event_end_at']) ? CarbonImmutable::parse($payload['event_end_at'])->toIso8601String() : null,
            'location' => $payload['location'] ?? null,
            'meeting_url' => $payload['meeting_url'] ?? null,
        ];

        $postPayload = array_merge($payload, [
            'type' => 'text',
            'metadata' => array_merge($payload['metadata'] ?? [], ['calendar_event' => $eventMetadata]),
            'scheduled_at' => $payload['event_start_at'],
        ]);

        return $this->postService->compose($community, $author, $postPayload);
    }

    public function upcomingEvents(Community $community, ?CarbonImmutable $start = null, ?CarbonImmutable $end = null): Collection
    {
        $start = $start ?? CarbonImmutable::now();

        $query = CommunityPost::query()
            ->where('community_id', $community->getKey())
            ->whereNotNull('metadata->calendar_event->event_start_at')
            ->where('metadata->calendar_event->event_start_at', '>=', $start->toIso8601String())
            ->orderBy('metadata->calendar_event->event_start_at');

        if ($end) {
            $query->where('metadata->calendar_event->event_start_at', '<=', $end->toIso8601String());
        }

        return $query->get();
    }

    public function cancelEvent(CommunityPost $post, ?string $reason = null): void
    {
        $metadata = $post->metadata ?? [];
        $metadata['calendar_event']['status'] = 'canceled';
        $metadata['calendar_event']['canceled_reason'] = $reason;
        $metadata['calendar_event']['canceled_at'] = CarbonImmutable::now()->toIso8601String();

        $post->metadata = $metadata;
        $post->save();
    }
}

