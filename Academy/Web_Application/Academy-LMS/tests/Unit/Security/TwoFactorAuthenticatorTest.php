<?php

namespace Tests\Unit\Security;

use App\Models\User;
use App\Support\Security\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

class TwoFactorAuthenticatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_verifies_totp_codes(): void
    {
        $user = User::factory()->create();
        $authenticator = app(TwoFactorAuthenticator::class);

        $secret = $authenticator->generateSecretFor($user);

        $this->assertNotEmpty($secret);

        $totp = TOTP::create($secret);
        $code = $totp->now();

        $this->assertTrue($authenticator->verify($user, $code));

        $codes = $authenticator->generateRecoveryCodes($user);
        $this->assertCount(10, $codes);

        $this->assertTrue($authenticator->useRecoveryCode($user, $codes[0]));
        $this->assertFalse($authenticator->useRecoveryCode($user, $codes[0]));
    }
}
