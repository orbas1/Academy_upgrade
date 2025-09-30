<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function testLoginScreenRendersWithExpectedFields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/login')
                ->assertPresent('form#login-form')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]')
                ->assertSeeIn('button[type="submit"]', 'Login');
        });
    }
}
