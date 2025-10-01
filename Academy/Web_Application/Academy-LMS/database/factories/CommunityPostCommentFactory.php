<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityPostCommentFactory extends Factory
{
    protected $model = CommunityPostComment::class;

    public function definition(): array
    {
        return [
            'community_id' => Community::factory(),
            'post_id' => CommunityPost::factory(),
            'author_id' => User::factory(),
            'body_md' => $this->faker->sentence(12),
            'body_html' => '<p>' . $this->faker->sentence(12) . '</p>',
            'is_pinned' => false,
            'is_locked' => false,
            'like_count' => 0,
            'reply_count' => 0,
        ];
    }
}
