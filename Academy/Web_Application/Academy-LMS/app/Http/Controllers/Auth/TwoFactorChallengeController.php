<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Security\TooManyDevicesException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\Security\DeviceTrustService;
use App\Support\Security\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactor,
        private readonly DeviceTrustService $deviceTrust
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(TwoFactorChallengeRequest $request): RedirectResponse
    {
        $userId = $request->session()->pull('login.two_factor.id');

        if (! $userId) {
            Session::flash('error', get_phrase('Your session has expired. Please log in again.'));

            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if (! $user) {
            Session::flash('error', get_phrase('Unable to locate your account. Please try again.'));

            return redirect()->route('login');
        }

        $rememberLogin = (bool) $request->session()->pull('login.two_factor.remember', false);
        $deviceToken = $request->session()->pull('login.two_factor.device_token');
        $ipAddress = $request->session()->pull('login.two_factor.ip', $request->getClientIp());

        $code = trim($request->input('code'));

        $validated = $this->twoFactor->verify($user, $code) || $this->twoFactor->useRecoveryCode($user, $code);

        if (! $validated) {
            return back()->withErrors([
                'code' => get_phrase('The provided authentication code is invalid or has expired.'),
            ]);
        }

        Auth::login($user, $rememberLogin);
        $request->session()->regenerate();

        try {
            $this->deviceTrust->recordLogin(
                $user,
                $deviceToken,
                $ipAddress,
                $request->session()->getId(),
                $request->boolean('remember_device')
            );
        } catch (TooManyDevicesException $exception) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Session::flash('success', $exception->getMessage());

            return redirect()->route('login');
        } catch (\Swift_TransportException $e) {
            Session::flash('error', 'We could not send the email. Please try again later.');
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        } catch (\Exception $e) {
            Session::flash('error', 'Something went wrong. Please try again.');
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
