<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'name' => $name,
            'tagline' => $this->faker->sentence(6),
            'visibility' => 'public',
            'join_policy' => 'open',
            'default_post_visibility' => 'community',
            'created_by' => User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
            'launched_at' => now()->subDays(5),
            'settings' => [],
        ];
    }
}
