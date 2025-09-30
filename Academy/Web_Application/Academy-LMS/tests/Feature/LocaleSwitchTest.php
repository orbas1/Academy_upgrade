<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    public function test_locale_update_sets_session_and_cookie(): void
    {
        $response = $this
            ->withSession(['_token' => 'test-token'])
            ->post(route('locale.update'), [
                '_token' => 'test-token',
                'locale' => 'es',
                'redirect_to' => '/dashboard',
            ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('locale', 'es');
        $response->assertCookie(config('localization.cookie_name'), 'es');
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this
            ->withSession(['_token' => 'test-token'])
            ->from('/settings')
            ->post(route('locale.update'), [
                '_token' => 'test-token',
                'locale' => 'jp',
            ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHasErrors(['locale']);
    }
}
