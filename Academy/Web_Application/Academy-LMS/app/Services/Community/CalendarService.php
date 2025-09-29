<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Contract for coordinating calendar sync across communities.
 */
interface CalendarService
{
    public function listCommunityEvents(Community $community, CommunityMember $member, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection;

    public function createCommunityEvent(Community $community, CommunityMember $member, array $payload): array;

    public function syncExternalCalendar(Community $community, CommunityMember $member): void;

    public function scheduleReminder(Community $community, int $eventId, CarbonInterface $when): void;
}
