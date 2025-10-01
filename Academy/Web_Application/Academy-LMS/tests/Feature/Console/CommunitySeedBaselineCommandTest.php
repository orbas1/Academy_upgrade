<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Communities\Models\CommunityCategory;
use App\Domain\Communities\Models\CommunityLevel;
use App\Domain\Communities\Models\CommunityPointsRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitySeedBaselineCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_foundation_records(): void
    {
        CommunityCategory::query()->delete();
        CommunityLevel::query()->delete();
        CommunityPointsRule::query()->delete();

        $this->artisan('community:seed-baseline', ['--force' => true])
            ->assertExitCode(0);

        $this->assertGreaterThanOrEqual(5, CommunityCategory::count());
        $this->assertGreaterThanOrEqual(4, CommunityLevel::whereNull('community_id')->count());
        $this->assertGreaterThanOrEqual(4, CommunityPointsRule::whereNull('community_id')->count());
    }
}
