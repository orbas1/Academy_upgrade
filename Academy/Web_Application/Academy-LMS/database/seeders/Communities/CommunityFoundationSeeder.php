<?php

namespace Database\Seeders\Communities;

use App\Domain\Communities\Models\CommunityCategory;
use App\Domain\Communities\Models\CommunityLevel;
use App\Domain\Communities\Models\CommunityPointsRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CommunityFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedDefaultLevels();
        $this->seedDefaultPointsRules();
    }

    private function seedCategories(): void
    {
        $categories = [
            [
                'slug' => 'general',
                'name' => 'General',
                'tagline' => 'Campus-wide announcements and wins',
                'description' => 'Cross-program updates, community highlights, and platform release notes.',
                'icon_path' => 'communities/icons/general.svg',
                'color_hex' => '#2563eb',
                'sort_order' => 1,
            ],
            [
                'slug' => 'study-groups',
                'name' => 'Study Groups',
                'tagline' => 'Peer cohorts working through the same track',
                'description' => 'Organize by subject and track to collaborate on assignments and challenges.',
                'icon_path' => 'communities/icons/study-groups.svg',
                'color_hex' => '#7c3aed',
                'sort_order' => 2,
            ],
            [
                'slug' => 'instructors',
                'name' => 'Instructors',
                'tagline' => 'Faculty coordination hub',
                'description' => 'Content planning, rubric reviews, and classroom best practices.',
                'icon_path' => 'communities/icons/instructors.svg',
                'color_hex' => '#059669',
                'sort_order' => 3,
            ],
            [
                'slug' => 'alumni',
                'name' => 'Alumni',
                'tagline' => 'Where alumni mentor and recruit',
                'description' => 'Industry mentorship, job leads, and success spotlights.',
                'icon_path' => 'communities/icons/alumni.svg',
                'color_hex' => '#ea580c',
                'sort_order' => 4,
            ],
            [
                'slug' => 'local-chapters',
                'name' => 'Local Chapters',
                'tagline' => 'Meet-ups in your city',
                'description' => 'Geo-based meetups and localized programming for learners.',
                'icon_path' => 'communities/icons/local-chapters.svg',
                'color_hex' => '#0ea5e9',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            CommunityCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }

    private function seedDefaultLevels(): void
    {
        $now = Carbon::now();

        $levels = [
            ['level' => 1, 'name' => 'Newbie', 'points_required' => 0, 'description' => 'Welcome aboard! Start engaging to climb the ranks.'],
            ['level' => 2, 'name' => 'Contributor', 'points_required' => 100, 'description' => 'You are sharing insights and feedback with peers.'],
            ['level' => 3, 'name' => 'Leader', 'points_required' => 500, 'description' => 'Your participation is inspiring the community.'],
            ['level' => 4, 'name' => 'Champion', 'points_required' => 1500, 'description' => 'You unlock premium perks and exclusive programming.'],
        ];

        foreach ($levels as $level) {
            CommunityLevel::updateOrCreate(
                ['community_id' => null, 'level' => $level['level']],
                [
                    'name' => $level['name'],
                    'badge_path' => null,
                    'points_required' => $level['points_required'],
                    'description' => $level['description'],
                    'rewards' => null,
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedDefaultPointsRules(): void
    {
        $rules = [
            ['action' => 'post', 'points' => 10, 'cooldown_seconds' => 0],
            ['action' => 'comment', 'points' => 4, 'cooldown_seconds' => 0],
            ['action' => 'like_received', 'points' => 2, 'cooldown_seconds' => 0],
            ['action' => 'login_streak', 'points' => 1, 'cooldown_seconds' => 86400],
            ['action' => 'course_complete', 'points' => 50, 'cooldown_seconds' => 0],
            ['action' => 'assignment_submit', 'points' => 15, 'cooldown_seconds' => 0],
        ];

        foreach ($rules as $rule) {
            CommunityPointsRule::updateOrCreate(
                ['community_id' => null, 'action' => $rule['action']],
                [
                    'points' => $rule['points'],
                    'cooldown_seconds' => $rule['cooldown_seconds'],
                    'is_active' => true,
                    'conditions' => null,
                    'metadata' => null,
                ]
            );
        }
    }
}
