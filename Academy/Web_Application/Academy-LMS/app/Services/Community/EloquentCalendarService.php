<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityCalendarService;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EloquentCalendarService implements CalendarService
{
    public function __construct(private readonly CommunityCalendarService $calendar)
    {
    }

    public function listCommunityEvents(
        Community $community,
        CommunityMember $member,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null
    ): Collection {
        $this->assertSameCommunity($community, $member);

        return $this->calendar
            ->upcomingEvents($community, $from, $to)
            ->map(fn (CommunityPost $post) => $this->mapEvent($post));
    }

    public function createCommunityEvent(Community $community, CommunityMember $member, array $payload): array
    {
        $this->assertSameCommunity($community, $member);

        $required = ['title', 'body_md', 'event_start_at'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $payload)) {
                throw new InvalidArgumentException("Missing required field {$field} for calendar event.");
            }
        }

        $post = $this->calendar->scheduleEvent($community, $member->user, $payload);

        return $this->mapEvent($post->fresh(['author']));
    }

    public function syncExternalCalendar(Community $community, CommunityMember $member): void
    {
        $this->assertSameCommunity($community, $member);

        $settings = $community->settings ?? [];
        $settings['calendar'] = array_merge($settings['calendar'] ?? [], [
            'last_synced_by' => $member->user_id,
            'last_synced_at' => CarbonImmutable::now()->toIso8601String(),
        ]);
        $community->settings = $settings;
        $community->save();
    }

    public function scheduleReminder(Community $community, int $eventId, CarbonImmutable $when): void
    {
        $post = CommunityPost::query()
            ->where('community_id', $community->getKey())
            ->findOrFail($eventId);

        $metadata = $post->metadata ?? [];
        $reminders = $metadata['calendar_event']['reminders'] ?? [];
        $reminders[] = [
            'scheduled_for' => $when->toIso8601String(),
        ];
        $metadata['calendar_event']['reminders'] = $reminders;
        $post->metadata = $metadata;
        $post->save();
    }

    private function mapEvent(CommunityPost $post): array
    {
        $metadata = [];
        $postMetadata = $post->metadata ?? [];
        if (is_array($postMetadata)) {
            $metadata = $postMetadata['calendar_event'] ?? [];
        }

        $title = $postMetadata['title'] ?? null;
        if (! $title) {
            $bodySource = $post->body_html ?? $post->body_md ?? '';
            $title = Str::limit(strip_tags($bodySource), 80, '…');
        }

        return [
            'id' => (int) $post->getKey(),
            'title' => $title ?: 'Untitled event',
            'starts_at' => $metadata['event_start_at'] ?? $post->scheduled_at?->toIso8601String(),
            'ends_at' => $metadata['event_end_at'] ?? null,
            'location' => $metadata['location'] ?? null,
            'meeting_url' => $metadata['meeting_url'] ?? null,
            'reminders' => $metadata['reminders'] ?? [],
            'author' => [
                'id' => $post->author_id,
                'name' => $post->author?->name,
            ],
        ];
    }

    private function assertSameCommunity(Community $community, CommunityMember $member): void
    {
        if ((int) $community->getKey() !== (int) $member->community_id) {
            throw new InvalidArgumentException('Member must belong to the community to manage calendar events.');
        }
    }
}
