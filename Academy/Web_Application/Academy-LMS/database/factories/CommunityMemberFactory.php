<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityMemberFactory extends Factory
{
    protected $model = CommunityMember::class;

    public function definition(): array
    {
        return [
            'community_id' => Community::factory(),
            'user_id' => User::factory(),
            'role' => 'member',
            'status' => 'active',
            'joined_at' => CarbonImmutable::now()->subDays(3),
            'last_seen_at' => CarbonImmutable::now()->subDay(),
            'is_online' => false,
            'points' => 0,
            'lifetime_points' => 0,
        ];
    }
}
