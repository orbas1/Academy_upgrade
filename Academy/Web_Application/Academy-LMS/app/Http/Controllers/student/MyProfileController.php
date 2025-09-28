<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\FileUploader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MyProfileController extends Controller
{
    public function index()
    {
        $page_data['user_details'] = User::find(auth()->user()->id);
        $view_path                 = 'frontend.' . get_frontend_settings('theme') . '.student.my_profile.index';
        return view($view_path, $page_data);
    }

    public function update(Request $request, $user_id)
    {
        $rules = [
            'name'  => 'required',
            'email' => 'required|email|unique:users,email,' . $user_id,
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data['name']      = $request->name;
        $data['email']     = $request->email;
        $data['phone']     = $request->phone;
        $data['website']   = $request->website;
        $data['facebook']  = $request->facebook;
        $data['twitter']   = $request->twitter;
        $data['linkedin']  = $request->linkedin;
        $data['skills']    = $request->skills;
        $data['biography'] = $request->biography;

        User::where('id', $user_id)->update($data);
        Session::flash('success', get_phrase('Profile updated successfully.'));
        return redirect()->back();
    }

    public function update_profile_picture(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp,tiff|max:3072',
        ]);

        // process file
        $file      = $request->photo;
        $file_name = Str::random(20) . '.' . $file->extension();
        $path      = 'uploads/users/' . auth()->user()->role . '/' . $file_name;
        FileUploader::upload($file, $path, null, null, 300);

        User::where('id', auth()->user()->id)->update(['photo' => $path]);
        Session::flash('success', get_phrase('Profile picture updated.'));
        return redirect()->back();
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:4',
            'confirm_password' => 'required|same:new_password',
        ]);

        // Check if the current password is correct
        if (!Auth::attempt(['email' => auth()->user()->email, 'password' => $request->current_password])) {
            Session::flash('error', 'Current password is incorrect.');
            return redirect()->back();
        }

        // Update password
        auth()->user()->update(['password' => Hash::make($request->new_password)]);

        Session::flash('success', 'Password changed successfully.');
        return redirect()->back();
    }
}
