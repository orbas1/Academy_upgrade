<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * Contract bridging existing classrooms with the communities module.
 */
interface ClassroomLinkService
{
    public function linkCourseToCommunity(Course $course, Community $community, array $options = []): void;

    public function unlinkCourseFromCommunity(Course $course, Community $community): void;

    public function syncAnnouncements(Course $course, Community $community): void;

    public function getLinkedCommunities(Course $course): Collection;

    public function getLinkedCourses(Community $community, ?CommunityMember $member = null): Collection;
}
