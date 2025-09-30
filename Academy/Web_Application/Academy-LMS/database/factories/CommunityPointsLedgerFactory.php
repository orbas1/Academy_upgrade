<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityPointsLedgerFactory extends Factory
{
    protected $model = CommunityPointsLedger::class;

    public function definition(): array
    {
        return [
            'member_id' => CommunityMember::factory(),
            'community_id' => fn (array $attributes) => CommunityMember::query()->find($attributes['member_id'])->community_id,
            'action' => 'post.created',
            'points_delta' => $this->faker->numberBetween(5, 25),
            'balance_after' => $this->faker->numberBetween(50, 500),
            'source_type' => User::class,
            'source_id' => User::factory(),
            'acted_by' => User::factory(),
            'occurred_at' => now()->subMinutes($this->faker->numberBetween(0, 500)),
            'metadata' => [],
        ];
    }
}
