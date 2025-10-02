<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Coordinates community events with personal calendars and reminders.
 */
interface CalendarService
{
    /**
     * Merge Orbas Learn community events with the member's personal calendar view.
     *
     * @param  array{range_start:Carbon,range_end:Carbon,include_personal?:bool}  $options
     * @return Collection<int, array>
     */
    public function mergedEvents(int $communityId, int $userId, array $options): Collection;

    /**
     * Schedule reminder notifications for an event instance.
     *
     * @param  array{remind_at:Carbon,channels?:array<int, string>}  $payload
     */
    public function scheduleReminder(int $eventId, int $userId, array $payload): void;

    /**
     * Generate an ICS payload for exporting a community event.
     *
     * @return string
     */
    public function generateIcs(int $eventId): string;
}
