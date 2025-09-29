<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullClassroomLinkService implements ClassroomLinkService
{
    use NotImplemented;
    public function linkCourseToCommunity(\App\Models\Course $course, \App\Models\Community\Community $community, array $options = []): void
    {
        $this->notImplemented();
    }

    public function unlinkCourseFromCommunity(\App\Models\Course $course, \App\Models\Community\Community $community): void
    {
        $this->notImplemented();
    }

    public function syncAnnouncements(\App\Models\Course $course, \App\Models\Community\Community $community): void
    {
        $this->notImplemented();
    }

    public function getLinkedCommunities(\App\Models\Course $course): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function getLinkedCourses(\App\Models\Community\Community $community, ?\App\Models\Community\CommunityMember $member = null): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }
}
