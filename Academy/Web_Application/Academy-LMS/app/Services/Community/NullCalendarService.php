<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullCalendarService implements CalendarService
{
    use NotImplemented;
    public function listCommunityEvents(\App\Models\Community\Community $community, \App\Models\Community\CommunityMember $member, ?\Carbon\CarbonInterface $from = null, ?\Carbon\CarbonInterface $to = null): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function createCommunityEvent(\App\Models\Community\Community $community, \App\Models\Community\CommunityMember $member, array $payload): array
    {
        $this->notImplemented();
    }

    public function syncExternalCalendar(\App\Models\Community\Community $community, \App\Models\Community\CommunityMember $member): void
    {
        $this->notImplemented();
    }

    public function scheduleReminder(\App\Models\Community\Community $community, int $eventId, \Carbon\CarbonInterface $when): void
    {
        $this->notImplemented();
    }
}
