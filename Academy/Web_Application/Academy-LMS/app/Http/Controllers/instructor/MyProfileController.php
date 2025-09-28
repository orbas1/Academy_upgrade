<?php

namespace App\Http\Controllers\instructor;

use App\Http\Controllers\Controller;
use App\Models\FileUploader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class MyProfileController extends Controller
{
    public function manage_profile()
    {
        return view('instructor.profile.index');
    }
    public function manage_profile_update(Request $request)
    {
        if ($request->type == 'general') {
            $profile['name']      = $request->name;
            $profile['email']     = $request->email;
            $profile['facebook']  = $request->facebook;
            $profile['twitter']   = $request->twitter;
            $profile['linkedin']  = $request->linkedin;
            $profile['video_url']  = $request->video_url;
            $profile['about']     = $request->about;
            $profile['skills']    = $request->skills;
            $profile['biography'] = $request->biography;

            if ($request->photo) {
                if (isset($request->photo) && $request->photo != '') {
                    $profile['photo'] = "uploads/users/admin/" . nice_file_name($request->title, $request->photo->extension());
                    FileUploader::upload($request->photo, $profile['photo'], 400, null, 200, 200);
                }
            }
            User::where('id', auth()->user()->id)->update($profile);
        } else {
            $old_pass_check = Auth::attempt(['email' => auth()->user()->email, 'password' => $request->current_password]);

            if (! $old_pass_check) {
                Session::flash('error', get_phrase('Current password wrong.'));
                return redirect()->back();
            }

            if ($request->new_password != $request->confirm_password) {
                Session::flash('error', get_phrase('Confirm password not same'));
                return redirect()->back();
            }

            $password = Hash::make($request->new_password);
            User::where('id', auth()->user()->id)->update(['password' => $password]);
        }
        Session::flash('success', get_phrase('Your changes has been saved.'));
        return redirect()->back();
    }

    public function manage_resume() 
    {
        return view('instructor.resume.index');
    }

    public function education_add(Request $request)
    {
        // Validate the form data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'institute' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:ongoing,completed',
            'description' => 'nullable|string'
        ]);

        // Check if 'end_date' is empty and 'status' is 'ongoing'
        if ($request->has('status') && $request->status === 'ongoing') {
            $validatedData['end_date'] = null;
        } else {
            $validatedData['status'] = 'completed';
        }

        // Format data for new education entry
        $newEducation = [
            'title' => $validatedData['title'],
            'institute' => $validatedData['institute'],
            'country' => $validatedData['country'],
            'city' => $validatedData['city'],
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
            'status' => $validatedData['status'],
            'description' => $validatedData['description']
        ];

        // Retrieve the currently authenticated user
        $user = Auth::user();

        // Decode the existing educations JSON data
        $educations = json_decode($user->educations, true) ?? [];

        // Append the new education entry
        $educations[] = $newEducation;

        // Save updated educations data back to the user's educations column as JSON
        $user->educations = json_encode($educations);
        $user->save();

        // Redirect or return a response, e.g., to the resume index page with a success message
        return redirect()->route('instructor.manage.resume')->with('success', 'Education added successfully.');
    }

    public function education_update(Request $request, $index)
    {
        // Validate the form data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'institute' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:ongoing,completed',
            'description' => 'nullable|string'
        ]);

        // Check if 'end_date' is empty and 'status' is 'ongoing'
        if ($request->has('status') && $request->status === 'ongoing') {
            $validatedData['end_date'] = null;
        } else {
            $validatedData['status'] = 'completed';
        }

        // Retrieve the currently authenticated user
        $user = Auth::user();

        // Decode the existing educations JSON data
        $educations = json_decode($user->educations, true) ?? [];

        // Check if the specified index exists in educations array
        if (isset($educations[$index])) {
            // Update the existing education entry
            $educations[$index] = [
                'title' => $validatedData['title'],
                'institute' => $validatedData['institute'],
                'country' => $validatedData['country'],
                'city' => $validatedData['city'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'status' => $validatedData['status'],
                'description' => $validatedData['description']
            ];

            // Save updated educations data back to the user's educations column as JSON
            $user->educations = json_encode($educations);
            $user->save();

            // Redirect or return a response, e.g., to the resume index page with a success message
            return redirect()->route('instructor.manage.resume')->with('success', 'Education updated successfully.');
        } else {
            // Handle the case where the specified education index does not exist
            return redirect()->route('instructor.manage.resume')->with('error', 'Education data not found for the specified index.');
        }
    }


    public function education_remove(Request $request, $index)
    {
        // Retrieve the currently authenticated user
        $user = Auth::user();

        // Decode the existing educations JSON data
        $educations = json_decode($user->educations, true) ?? [];

        // Check if the index exists in the educations array
        if (isset($educations[$index])) {
            // Remove the specific education entry
            unset($educations[$index]);

            // Re-index the array to ensure no gaps in the indices
            $educations = array_values($educations);

            // Update the user's educations column with the modified array
            $user->educations = json_encode($educations);
            $user->save();

            return redirect()->route('instructor.manage.resume')->with('success', 'Education deleted successfully.');
        } else {
            return redirect()->route('instructor.manage.resume')->with('error', 'Education not found.');
        }
    }
}
