<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Support\Security\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticator $authenticator)
    {
    }

    public function prepare(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            Session::flash('status', get_phrase('Two-factor authentication is already enabled.'));

            return back();
        }

        $this->authenticator->generateSecretFor($user);

        Session::flash('status', get_phrase('Scan the QR code with your authenticator app and confirm to enable two-factor authentication.'));

        return back();
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $code = trim($request->input('code'));

        if (! $this->authenticator->verify($user, $code)) {
            throw ValidationException::withMessages([
                'code' => get_phrase('The provided authentication code is invalid.'),
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $codes = $this->authenticator->generateRecoveryCodes($user);
        Session::flash('two_factor_recovery_codes', $codes);
        Session::flash('status', get_phrase('Two-factor authentication is now enabled. Store your recovery codes in a safe place.'));

        return back();
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->clearTwoFactorCredentials();

        Session::flash('status', get_phrase('Two-factor authentication has been disabled.'));

        return back();
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            Session::flash('error', get_phrase('Enable two-factor authentication before generating recovery codes.'));

            return back();
        }

        $codes = $this->authenticator->generateRecoveryCodes($user);
        Session::flash('two_factor_recovery_codes', $codes);
        Session::flash('status', get_phrase('New recovery codes generated. Previous codes are no longer valid.'));

        return back();
    }
}
