<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\ProfileActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProfileActivityFactory extends Factory
{
    protected $model = ProfileActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'community_id' => Community::factory(),
            'activity_type' => 'community_post.published',
            'subject_type' => 'community_post',
            'subject_id' => $this->faker->numberBetween(1, 10_000),
            'idempotency_key' => Str::uuid()->toString(),
            'occurred_at' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
            'context' => [
                'example' => true,
            ],
        ];
    }
}
