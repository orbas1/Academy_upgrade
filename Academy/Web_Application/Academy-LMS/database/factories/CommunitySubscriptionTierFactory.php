<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommunitySubscriptionTierFactory extends Factory
{
    protected $model = CommunitySubscriptionTier::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'community_id' => Community::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'currency' => 'USD',
            'price_cents' => $this->faker->numberBetween(500, 1500),
            'billing_interval' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'trial_days' => $this->faker->numberBetween(0, 30),
            'is_public' => true,
            'benefits' => [
                'community_access' => true,
                'bonus_sessions' => $this->faker->numberBetween(0, 3),
            ],
            'metadata' => [],
            'published_at' => now()->subDay(),
        ];
    }
}
