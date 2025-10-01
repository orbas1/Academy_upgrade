<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Communities\Models\Community;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CommunityEnableFeatureCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::delete(storage_path('app/feature-flags.json'));
        File::delete(storage_path('app/feature-rollouts.json'));
    }

    public function test_it_updates_feature_flags_and_rollout_metadata(): void
    {
        $community = Community::factory()->create(['slug' => 'beta-club']);

        $this->artisan('community:enable-feature', [
            '--flag' => 'community_profile_activity',
            '--percentage' => 50,
            '--segment' => 'internal,beta',
            '--community' => 'beta-club',
            '--force' => true,
        ])->assertExitCode(0);

        $flags = json_decode((string) file_get_contents(storage_path('app/feature-flags.json')), true);
        $this->assertTrue($flags['community_profile_activity']);

        $rollouts = json_decode((string) file_get_contents(storage_path('app/feature-rollouts.json')), true);
        $this->assertSame(50, $rollouts['community_profile_activity']['percentage']);
        $this->assertSame(['internal', 'beta'], $rollouts['community_profile_activity']['segments']);

        $community->refresh();
        $this->assertTrue($community->launched_at !== null);
        $this->assertEquals(['internal', 'beta'], $community->settings['rollout']['beta']['segments']);
    }
}
