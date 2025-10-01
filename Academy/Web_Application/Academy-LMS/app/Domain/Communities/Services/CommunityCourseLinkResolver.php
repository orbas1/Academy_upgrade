<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\Models\Community;
use Illuminate\Database\Eloquent\Collection;

class CommunityCourseLinkResolver
{
    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function buildCourseCommunityMap(): array
    {
        /** @var Collection<int, Community> $communities */
        $communities = Community::query()
            ->select(['id', 'settings'])
            ->get();

        return $this->mapCommunitiesToCourses($communities);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractLinks(Community $community): array
    {
        $settings = $community->settings ?? [];

        if (! is_array($settings)) {
            return [];
        }

        $links = $settings['classroom_links'] ?? [];

        if (! is_array($links)) {
            return [];
        }

        $normalised = [];

        foreach ($links as $key => $payload) {
            $courseId = $this->parseCourseId($key, $payload);
            if (! $courseId) {
                continue;
            }

            $normalised[$courseId] = [
                'course_id' => $courseId,
                'default_role' => $this->normaliseRole($payload['default_role'] ?? 'member'),
                'visibility' => $payload['visibility'] ?? 'member',
                'metadata' => array_filter([
                    'added_at' => $payload['added_at'] ?? null,
                    'source' => $payload['source'] ?? 'classrooms',
                ]),
            ];
        }

        return $normalised;
    }

    /**
     * @param  Collection<int, Community>  $communities
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function mapCommunitiesToCourses(Collection $communities): array
    {
        $map = [];

        foreach ($communities as $community) {
            $links = $this->extractLinks($community);
            if ($links === []) {
                continue;
            }

            foreach ($links as $courseId => $link) {
                $map[$courseId] ??= [];
                $map[$courseId][$community->getKey()] = $link + [
                    'community_id' => $community->getKey(),
                ];
            }
        }

        return $map;
    }

    private function parseCourseId(int|string $key, mixed $payload): ?int
    {
        if (is_array($payload) && isset($payload['course_id'])) {
            $courseId = (int) $payload['course_id'];
            return $courseId > 0 ? $courseId : null;
        }

        $courseId = (int) $key;
        return $courseId > 0 ? $courseId : null;
    }

    private function normaliseRole(string $role): string
    {
        $normalised = strtolower($role);

        $map = [
            'student' => 'member',
            'learner' => 'member',
            'participant' => 'member',
            'teacher' => 'admin',
            'instructor' => 'admin',
            'facilitator' => 'moderator',
            'assistant' => 'moderator',
        ];

        return $map[$normalised] ?? match ($normalised) {
            'owner', 'admin', 'moderator', 'member' => $normalised,
            default => 'member',
        };
    }
}
