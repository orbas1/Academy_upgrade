<?php

declare(strict_types=1);

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CommunityFlowE2ETest extends DuskTestCase
{
    use RefreshDatabase;

    public function test_complete_community_flow_executes_successfully(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser
                ->visit('/testing/community-flow')
                ->assertSee('Community Flow Test Harness')
                ->press('@run-flow')
                ->waitFor('@run-flow', 5)
                ->waitFor('#result[data-status="complete"]', 10)
                ->assertSeeIn('#result', 'leaderboard')
                ->assertSeeIn('#result', 'notifications')
                ->assertSeeIn('#result', 'points');
        });
    }
}
