<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FileUploader;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();

        if (get_frontend_settings('recaptcha_status') == true && check_recaptcha($input['g-recaptcha-response']) == false) {

            Session::flash('error', get_phrase('Recaptcha verification failed'));

            return redirect(route('register.form'));
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user_data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'student',
            'status' => 1,
            'password' => Hash::make($request->password),
        ];

        if (get_settings('student_email_verification') != 1) {
            $user_data['email_verified_at'] = Carbon::now();
        }

        $user = User::create($user_data);


        event(new Registered($user));

        Auth::login($user);

        // If applying as an instructor, process the application
        if ($request->has('instructor')) {

            // Check if application already exists
            if (Application::where('user_id', $user->id)->exists()) {
                Session::flash('error', get_phrase('Your request is in process. Please wait for admin to respond.'));
                return redirect()->route('become.instructor');
            }

            // Process instructor application
            $application['user_id'] = $user->id;
            $application['phone'] = $request->phone;
            $application['description'] = $request->description;

            // Upload document
            $doc = $request->file('document');
            $application['document'] = 'uploads/applications/' . $user->id . Str::random(20) . '.' . $doc->extension();

            FileUploader::upload($doc, $application['document'], null, null, 300);

            // Store application
            Application::insert($application);

            Session::flash('success', get_phrase('Your application has been submitted.'));
        }

        return redirect(RouteServiceProvider::HOME);
    }
}
