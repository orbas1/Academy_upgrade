<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorSubject;
use App\Models\TutorCategory;
use App\Models\TutorBooking;
use App\Models\TutorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TutorBookingController extends Controller
{
    public function subjects()
    {
        $page_data['subjects'] = TutorSubject::orderBy('id', 'asc')->paginate(10);
        return view('admin.tutor_booking.subjects', $page_data);
    }

    public function tutor_subject_create()
    {
    }

    public function tutor_subject_store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
        ]);

        if (TutorSubject::where('slug', slugify($request->name))->count() > 0) {
            return redirect(route('admin.tutor_subjects'))->with('error', get_phrase('There cannot be more than one subject with the same name. Please change your subject name'));
        }

        $data['name'] = $request->name;
        $data['slug'] = slugify($request->name);
        $data['status'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        TutorSubject::insert($data);

        return redirect(route('admin.tutor_subjects'))->with('success', get_phrase('Subject added successfully'));
    }

    public function tutor_subject_edit()
    {
    }

    public function tutor_subject_update(Request $request, $id)
    {
        $query = TutorSubject::where('id', $id);
        $pre_data = TutorSubject::where('id', $id)->first();

        $validated = $request->validate([
            'name' => 'required|max:255',
        ]);

        if (TutorSubject::where('slug', slugify($request->name))->where('id', '!=', $id)->count() > 0) {
            return redirect(route('admin.tutor_subjects'))->with('error', get_phrase('There cannot be more than one subject with the same name. Please change your subject name'));
        }

        $data['name'] = $request->name;
        $data['slug'] = slugify($request->name);
        $data['status'] = 1;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $query->update($data);

        return redirect(route('admin.tutor_subjects'))->with('success', get_phrase('Subject updated successfully'));
    }

    public function tutor_subject_status($id, $status)
    {
        $query = TutorSubject::where('id', $id);

        if($status == 'active') {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }

        $query->update($data);

        return redirect(route('admin.tutor_subjects'))->with('success', get_phrase('Subject status updated successfully'));
    }

    public function tutor_subject_delete($id)
    {
        $query = TutorSubject::where('id', $id);

        $query->delete();

        return redirect(route('admin.tutor_subjects'))->with('success', get_phrase('Subject deleted successfully'));
    }

    public function tutor_categories()
    {
        $page_data['categories'] = TutorCategory::orderBy('id', 'asc')->paginate(10);
        return view('admin.tutor_booking.categories', $page_data);
    }

    public function tutor_category_create()
    {
    }

    public function tutor_category_store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
        ]);

        if (TutorCategory::where('slug', slugify($request->name))->count() > 0) {
            return redirect(route('admin.tutor_categories'))->with('error', get_phrase('There cannot be more than one subject category with the same name. Please change your subject category name'));
        }

        $data['status'] = 1;
        $data['name'] = $request->name;
        $data['slug'] = slugify($request->name);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        TutorCategory::insert($data);

        return redirect(route('admin.tutor_categories'))->with('success', get_phrase('Subject category added successfully'));
    }

    public function tutor_category_edit()
    {
    }

    public function tutor_category_update(Request $request, $id)
    {
        $query = TutorCategory::where('id', $id);
        $pre_data = TutorCategory::where('id', $id)->first();

        $validated = $request->validate([
            'name' => 'required|max:255',
        ]);

        if (TutorCategory::where('slug', slugify($request->name))->where('id', '!=', $id)->count() > 0) {
            return redirect(route('admin.tutor_categories'))->with('error', get_phrase('There cannot be more than one subject category with the same name. Please change your subject category name'));
        }

        $data['status'] = $pre_data->status;
        $data['name'] = $request->name;
        $data['slug'] = slugify($request->name);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $query->update($data);

        return redirect(route('admin.tutor_categories'))->with('success', get_phrase('Category updated successfully'));
    }

    public function tutor_category_status($id, $status)
    {
        $query = TutorCategory::where('id', $id);

        if($status == 'active') {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }

        $query->update($data);

        return redirect(route('admin.tutor_categories'))->with('success', get_phrase('Category status updated successfully'));
    }

    public function tutor_category_delete($id)
    {
        $query = TutorCategory::where('id', $id);
        $query->delete();

        return redirect(route('admin.tutor_categories'))->with('success', get_phrase('Subject category deleted successfully'));
    }

}
