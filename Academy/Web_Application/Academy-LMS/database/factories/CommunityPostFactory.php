<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommunityPostFactory extends Factory
{
    protected $model = CommunityPost::class;

    public function definition(): array
    {
        $body = $this->faker->paragraphs(3, true);

        return [
            'community_id' => Community::factory(),
            'author_id' => User::factory(),
            'type' => 'text',
            'body_md' => $body,
            'body_html' => sprintf('<p>%s</p>', Str::of($body)->replace('\n', '</p><p>')),
            'media' => [],
            'is_pinned' => false,
            'is_locked' => false,
            'visibility' => 'community',
            'paywall_tier_id' => null,
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'view_count' => 0,
            'published_at' => now(),
            'mentions' => [],
            'topics' => [],
            'metadata' => [
                'title' => Str::title($this->faker->sentence(4)),
            ],
        ];
    }
}
