<?php

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Providers\RouteServiceProvider;
// use Illuminate\Auth\Events\Verified;
// use Illuminate\Foundation\Auth\EmailVerificationRequest;
// use Illuminate\Http\RedirectResponse;

// class VerifyEmailController extends Controller
// {
//     /**
//      * Mark the authenticated user's email address as verified.
//      */
//     public function __invoke(EmailVerificationRequest $request): RedirectResponse
//     {
//         if ($request->user()->hasVerifiedEmail()) {
//             return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
//         }

//         if ($request->user()->markEmailAsVerified()) {
//             event(new Verified($request->user()));
//         }

//         return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
//     }
// }















namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __construct(Type $var = null) {
        $this->var = $var;
        $user = User::findOrFail(request()->route('id'));
        Auth::login($user);
    }
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Fetch the user directly from the database using the route parameters
        $user = Auth::user();


        // Validate the hash matches the user's email
        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid or expired verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }

        // Mark email as verified and trigger the Verified event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Optionally log the user in after successful verification

        return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
    }
}
