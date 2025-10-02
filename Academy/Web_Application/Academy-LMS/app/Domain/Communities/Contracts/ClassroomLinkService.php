<?php

namespace App\Domain\Communities\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Syncs courses and classroom announcements with Orbas Learn communities.
 */
interface ClassroomLinkService
{
    /**
     * Link a course or classroom to a community.
     *
     * @param  array{linked_by:int,metadata?:array}  $payload
     * @return array{link_id:int,course_id:int,community_id:int,created_at:Carbon}
     */
    public function attachCourse(int $courseId, int $communityId, array $payload = []): array;

    /**
     * Detach a course or classroom from the community.
     */
    public function detachCourse(int $courseId, int $communityId, int $actorId): void;

    /**
     * Mirror announcements from the linked classroom into the community feed.
     *
     * @param  array{since?:Carbon|null,limit?:int}  $options
     * @return Collection<int, array>
     */
    public function mirrorAnnouncements(int $courseId, int $communityId, array $options = []): Collection;
}
