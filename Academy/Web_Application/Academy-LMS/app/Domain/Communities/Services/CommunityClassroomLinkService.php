<?php

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;

class CommunityClassroomLinkService
{
    public function linkCourse(Community $community, int $courseId, array $options = []): Community
    {
        $settings = $community->settings ?? [];
        $links = $settings['classroom_links'] ?? [];

        $links[$courseId] = array_merge($links[$courseId] ?? [], [
            'course_id' => $courseId,
            'visibility' => $options['visibility'] ?? 'member',
            'default_role' => $options['default_role'] ?? 'student',
            'added_at' => now()->toIso8601String(),
        ]);

        $settings['classroom_links'] = $links;
        $community->settings = $settings;
        $community->save();

        return $community;
    }

    public function unlinkCourse(Community $community, int $courseId): Community
    {
        $settings = $community->settings ?? [];
        $links = $settings['classroom_links'] ?? [];

        unset($links[$courseId]);

        $settings['classroom_links'] = $links;
        $community->settings = $settings;
        $community->save();

        return $community;
    }

    public function getLinkedCourses(Community $community): array
    {
        $settings = $community->settings ?? [];

        return array_values($settings['classroom_links'] ?? []);
    }
}

