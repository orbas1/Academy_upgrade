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
                ->waitFor('#result[data-status="complete"]', 15)
                ->assertSeeIn('#result', '"status": "ok"')
                ->assertSeeIn('#result', '"member_count": 2')
                ->assertSeeIn('#result', '"like_count": 1')
                ->assertSeeIn('#result', 'community_flow_v1')
                ->assertSeeIn('#result', 'Congrats on the launch!')
                ->assertSeeIn('#result', '"leaderboard"')
                ->assertSeeIn('#result', '"notifications"');
        });
    }
}
