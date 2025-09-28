<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\Mailer;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuizSubmission;
use App\Models\FileUploader;
use App\Models\Section;
use App\Models\SeoField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $category   = 'all';
        $status     = 'all';
        $instructor = 'all';
        $price      = 'all';

        $query  = Course::query();
        $query1 = Course::query();

        if (isset($request->category) && $request->category != '' && $request->category != 'all') {

            $category_details = Category::where('slug', $request->category)->first();
            if ($category_details->parent_id > 0) {
                $page_data['child_cat'] = $request->category;
                $query                  = $query->where('category_id', $category_details->id);
            } else {
                $sub_cat_id              = Category::where('parent_id', $category_details->id)->pluck('id')->toArray();
                $sub_cat_id[] = $category_details->id;
                $query                   = $query->whereIn('category_id', $sub_cat_id);
                $page_data['parent_cat'] = $request->category;
            }
        }

        // search filter
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $query = $query->where('title', 'LIKE', '%' . $_GET['search'] . '%');
        }

        // selected price
        if (isset($_GET['price']) && $_GET['price'] != '' && $_GET['price'] != 'all') {
            $search_price = 2;
            if ($_GET['price'] == 'free') {
                $search_price = 0;
            } elseif ($_GET['price'] == 'paid') {
                $search_price = 1;
            }
            $query = $query->where('is_paid', $search_price);
            $price = $_GET['price'];
        }
        // selected instructor
        if (isset($_GET['instructor']) && $_GET['instructor'] != '' && $_GET['instructor'] != 'all') {
            $query      = $query->where('user_id', $_GET['instructor']);
            $instructor = $_GET['instructor'];
        }

        // status filter
        if (isset($_GET['status']) && $_GET['status'] != '' && $_GET['status'] != 'all') {

            if ($_GET['status'] == 'active') {
                $search_status = 'active';
                $query         = $query->where('status', $search_status);
            } elseif ($_GET['status'] == 'inactive') {
                $search_status = 'inactive';
                $query         = $query->where('status', $search_status);
            } elseif ($_GET['status'] == 'pending') {
                $search_status = 'pending';
                $query         = $query->where('status', $search_status);
            } elseif ($_GET['status'] == 'private') {
                $search_status = 'private';
                $query         = $query->where('status', $search_status);
            } elseif ($_GET['status'] == 'upcoming') {
                $search_status = 'upcoming';
                $query         = $query->where('status', $search_status);
            } elseif ($_GET['status'] == 'draft') {
                $search_status = 'draft';
                $query         = $query->where('status', $search_status);
            }
            $status = $_GET['status'];
        }
        $page_data['status']           = $status;
        $page_data['instructor']       = $instructor;
        $page_data['price']            = $price;
        $page_data['courses']          = $query->orderBy('id', 'desc')->paginate(20)->appends(request()->query());
        $page_data['pending_courses']  = Course::where('status', 'pending')->count();
        $page_data['active_courses']   = Course::where('status', 'active')->count();
        $page_data['upcoming_courses'] = Course::where('status', 'upcoming')->count();
        $page_data['paid_courses']     = Course::where('is_paid', 1)->count();
        $page_data['free_courses']     = Course::where('is_paid', 0)->count();

        return view('admin.course.index', $page_data);
    }

    public function create()
    {
        return view('admin.course.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                 => 'required|max:255',
            'category_id'           => 'required|numeric|min:1',
            'course_type'           => 'required|in:general,scorm',
            'status'                => 'required|in:active,pending,draft,private,upcoming,inactive',
            'level'                 => 'required|in:everyone,beginner,intermediate,advanced',
            'language'              => 'required',
            'is_paid'               => Rule::in(['0', '1']),
            'price'                 => 'required_if:is_paid,1|min:1|nullable|numeric',
            'discount_flag'         => Rule::in(['', '1']),
            'discounted_price'      => 'required_if:discount_flag,1|min:1|nullable|numeric',
            'enable_drip_content'   => Rule::in(['0', '1']),
            'requirements'          => 'array',
            'outcomes'              => 'array',
            'faqs'                  => 'array',
            'instructors'           => 'required|array|min:1',
        ]);
        //for normal form submission

        $data['title']                 = $request->title;
        $data['slug']                  = slugify($request->title);
        $data['user_id']               = auth()->user()->id;
        $data['category_id']           = $request->category_id;
        $data['course_type']           = $request->course_type;
        $data['status']                = $request->status;
        $data['level']                 = $request->level;
        $data['language']              = strtolower($request->language);
        $data['is_paid']               = $request->is_paid;
        $data['price']                 = $request->price;
        $data['discount_flag']         = $request->discount_flag;
        $data['discounted_price']      = $request->discounted_price;
        $data['enable_drip_content']   = $request->enable_drip_content;


        $drip_content_settings = '{"lesson_completion_role":"percentage","minimum_duration":15,"minimum_percentage":"30","locked_lesson_message":"&lt;h3 xss=&quot;removed&quot; style=&quot;text-align: center; &quot;&gt;&lt;span xss=&quot;removed&quot;&gt;&lt;strong&gt;Permission denied!&lt;\/strong&gt;&lt;\/span&gt;&lt;\/h3&gt;&lt;p xss=&quot;removed&quot; style=&quot;text-align: center; &quot;&gt;&lt;span xss=&quot;removed&quot;&gt;This course supports drip content, so you must complete the previous lessons.&lt;\/span&gt;&lt;\/p&gt;"}';

        $data['drip_content_settings'] = $drip_content_settings;

        $meta_keywords     = '';
        $meta_keywords_arr = json_decode($request->meta_keywords, true);
        if (is_array($meta_keywords_arr)) {
            foreach ($meta_keywords_arr as $key => $keyword) {
                if ($key > 0) {
                    $meta_keywords .= ',' . $keyword['value'];
                } else {
                    $meta_keywords .= $keyword['value'];
                }
            }
        }
        $data['meta_keywords']    = $meta_keywords;
        $data['meta_description'] = $request->meta_description;

        $data['short_description'] = $request->short_description;
        $data['description']       = $request->description;

        //Course expiry period
        if ($request->expiry_period == 'limited_time' && is_numeric($request->number_of_month) && $request->number_of_month > 0) {
            $data['expiry_period'] = $request->number_of_month;
        } else {
            $data['expiry_period'] = null;
        }

        //Remove empty value by using array filter function
        if (isset($request->requirements) && $request->requirements != '') {

            $data['requirements'] = json_encode(array_filter($request->requirements, fn ($value) => !is_null($value) && $value !== ''));
        }
        if (isset($request->outcomes) && $request->outcomes != '') {

            $data['outcomes'] = json_encode(array_filter($request->outcomes, fn ($value) => !is_null($value) && $value !== ''));
        }

        if (isset($request->faq_title) && $request->faq_title != '') {

            $faqs = [];
            foreach ($request->faq_title as $key => $title) {
                if ($title != '') {
                    $faqs[] = ['title' => $title, 'description' => $request->faq_description[$key]];
                }
            }
            $data['faqs'] = json_encode($faqs);
        }

        $data['instructor_ids'] = json_encode($request->instructors);
        $data['created_at']  = date('Y-m-d H:i:s');
        $data['updated_at']  = date('Y-m-d H:i:s');

        if ($request->thumbnail) {
            $data['thumbnail'] = "uploads/course-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $data['thumbnail'], 400, null, 200, 200);
        }

        if ($request->banner) {
            $data['banner'] = "uploads/course-banner/" . nice_file_name($request->title, $request->banner->extension());
            FileUploader::upload($request->banner, $data['banner'], 1400, null, 300, 300);
        }

        if ($request->preview) {
            $data['preview'] = "uploads/course-preview/" . nice_file_name($request->title, $request->preview->extension());
            FileUploader::upload($request->preview, $data['preview']);
        }

        $course_id = Course::insertGetId($data);
        Course::where('id', $course_id)->update(['slug' => slugify($request->title . '-' . $course_id)]);

        //for normal form submission
        return redirect(route('admin.course.edit', ['id' => $course_id]))->with('success', get_phrase('Course added successfully'));
    }

    public function edit($course_id = "", Request $request)
    {

        $data['course_details'] = Course::where('id', $course_id)->first();
        $data['sections']       = Section::where('course_id', $course_id)->orderBy('sort')->get();
        return view('admin.course.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $query = Course::where('id', $id);

        if ($request->tab == '') {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $rules = $data = [];
        if ($request->tab == 'basic') {
            $rules = [
                'title'       => 'required|max:255',
                'category_id' => 'required|numeric|min:1',
                'level'       => 'required|in:everyone,beginner,intermediate,advanced',
                'language'    => 'required',
                'status'      => 'required|in:active,pending,draft,private,upcoming,inactive',
                'instructors' => 'required|array|min:1',
            ];

            $data['title']             = $request->title;
            $data['slug']              = slugify($request->title . '-' . $id);
            $data['short_description'] = $request->short_description;
            $data['description']       = removeScripts($request->description);
            $data['category_id']       = $request->category_id;
            $data['level']             = $request->level;
            $data['language']          = strtolower($request->language);
            $data['status']            = $request->status;
            $data['instructor_ids']       = json_encode($request->instructors);
        } elseif ($request->tab == 'pricing') {
            $rules = [
                'is_paid'          => Rule::in(['0', '1']),
                'price'            => 'required_if:is_paid,1|min:1|nullable|numeric',
                'discount_flag'    => Rule::in(['', '1']),
                'discounted_price' => 'required_if:discount_flag,1|min:1|nullable|numeric',
            ];

            $data['is_paid']          = $request->is_paid;
            $data['price']            = $request->price;
            $data['discount_flag']    = $request->discount_flag;
            $data['discounted_price'] = $request->discounted_price;

            //Course expiry period
            if ($request->expiry_period == 'limited_time' && is_numeric($request->number_of_month) && $request->number_of_month > 0) {
                $data['expiry_period'] = $request->number_of_month;
            } else {
                $data['expiry_period'] = null;
            }

        } elseif ($request->tab == 'info') {
            $rules = [
                'requirements' => 'array',
                'outcomes'     => 'array',
                'faqs'         => 'array',
            ];

            //Remove empty value by using array filter function
            $data['requirements'] = json_encode(array_filter($request->requirements, fn ($value) => !is_null($value) && $value !== ''));
            $data['outcomes']     = json_encode(array_filter($request->outcomes, fn ($value) => !is_null($value) && $value !== ''));

            $faqs = [];
            foreach ($request->faq_title as $key => $title) {
                if ($title != '') {
                    $faqs[] = ['title' => $title, 'description' => $request->faq_description[$key]];
                }
            }
            $data['faqs'] = json_encode($faqs);
        } elseif ($request->tab == 'media') {
            if ($request->thumbnail) {
                $data['thumbnail'] = "uploads/course-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
                FileUploader::upload($request->thumbnail, $data['thumbnail'], 400, null, 200, 200);
                remove_file($query->first()->thumbnail);
            }

            if ($request->banner) {
                $data['banner'] = "uploads/course-banner/" . nice_file_name($request->title, $request->banner->extension());
                FileUploader::upload($request->banner, $data['banner'], 1400, null, 300, 300);
                remove_file($query->first()->banner);
            }

            if($request->preview_video_provider == 'link'){
                $data['preview'] = $request->preview_link;
            }elseif($request->preview_video_provider == 'file' && $request->preview){
                $data['preview'] = "uploads/course-preview/" . nice_file_name($request->title, $request->preview->extension());
                FileUploader::upload($request->preview, $data['preview']);
                remove_file($query->first()->preview);
            }
            
        } elseif ($request->tab == 'seo') {
            $course_details = $query->first();
            $SeoField = SeoField::where('name_route', 'course.details')->where('course_id', $course_details->id)->first();

            $seo_data['course_id'] = $id;
            $seo_data['route'] = 'Course Details';
            $seo_data['name_route'] = 'course.details';
            $seo_data['meta_title'] = $request->meta_title;
            $seo_data['meta_description'] = $request->meta_description;
            $seo_data['meta_robot'] = $request->meta_robot;
            $seo_data['canonical_url'] = $request->canonical_url;
            $seo_data['custom_url'] = $request->custom_url;
            $seo_data['json_ld'] = $request->json_ld;
            $seo_data['og_title'] = $request->og_title;
            $seo_data['og_description'] = $request->og_description;
            $seo_data['created_at'] = date('Y-m-d H:i:s');
            $seo_data['updated_at'] = date('Y-m-d H:i:s');


            $meta_keywords_arr = json_decode($request->meta_keywords, true);
            $meta_keywords = '';
            if (is_array($meta_keywords_arr)) {
                foreach ($meta_keywords_arr as $arr_key => $arr_val) {
                    $meta_keywords .= $meta_keywords == '' ? $arr_val['value'] : ', ' . $arr_val['value'];
                }
                $seo_data['meta_keywords'] = $meta_keywords;
            }


            if ($request->og_image) {
                $originalFileName = $course_details->id . '-' . $request->og_image->getClientOriginalName();
                $destinationPath = 'uploads/seo-og-images/' . $originalFileName;
                // Move the file to the destination path
                FileUploader::upload($request->og_image, $destinationPath, 600);
                $seo_data['og_image'] = $destinationPath;
            }

            if ($SeoField) {
                if ($request->og_image) {
                    remove_file($SeoField->og_image);
                }

                SeoField::where('name_route', 'course.details')->where('course_id', $course_details->id)->update($seo_data);
            } else {
                SeoField::insert($seo_data);
            }
        } elseif ($request->tab == 'drip-content') {
            $rules = [
                'enable_drip_content'   => Rule::in(['0', '1']),
            ];

            $data['enable_drip_content']   = $request->enable_drip_content;

            $lesson_completion_role = htmlspecialchars($request->input('lesson_completion_role'));
            $minimum_duration_input = htmlspecialchars($request->input('minimum_duration'));
            $minimum_percentage = htmlspecialchars($request->input('minimum_percentage'));
            $locked_lesson_message = htmlspecialchars($request->input('locked_lesson_message'));

            // Convert time (HH:MM:SS) to seconds
            $time_parts = explode(':', $minimum_duration_input);
            $seconds = ($time_parts[0] * 3600) + ($time_parts[1] * 60) + $time_parts[2];

            $drip_data = [
                'lesson_completion_role' => $lesson_completion_role,
                'minimum_duration'       => $seconds,
                'minimum_percentage'     => $minimum_percentage,
                'locked_lesson_message'  => $locked_lesson_message,
            ];

            $data['drip_content_settings'] = json_encode($drip_data);
        }

        //For ajax form submission
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return json_encode(array('validationError' => $validator->getMessageBag()->toArray()));
        }

        $query->update($data);

        //For ajax form submission
        return ['success' => get_phrase('Course updated successfully')];

    }

    public function status($type, $id)
    {
        if ($type == 'active') {
            Course::where('id', $id)->update(['status' => 'active']);
        } elseif ($type == 'pending') {
            Course::where('id', $id)->update(['status' => 'pending']);
        } elseif ($type == 'inactive') {
            Course::where('id', $id)->update(['status' => 'inactive']);
        } elseif ($type == 'upcoming') {
            Course::where('id', $id)->update(['status' => 'upcoming']);
        } elseif ($type == 'private') {
            Course::where('id', $id)->update(['status' => 'private']);
        } else {
            Course::where('id', $id)->update(['status' => 'draft']);
        }

        return redirect(route('admin.courses'))->with('success', get_phrase('Course status changed successfully'));
    }

    public function delete($id)
    {
        // Find the course
        $course = Course::find($id);

        if (!$course) {
            Session::flash('error', get_phrase('Course not found'));
            return redirect()->back();
        }

        // Get all lessons associated with the course
        $lessons = Lesson::where('course_id', $id)->get();

        foreach ($lessons as $lesson) {
            // Remove lesson files
            remove_file($lesson->lesson_src);
            remove_file('uploads/lesson_file/attachment/' . $lesson->attachment);

            // If the lesson is a quiz, delete related questions and submissions
            if ($lesson->lesson_type == 'quiz') {
                Question::where('quiz_id', $lesson->id)->each(function ($question) {
                    $question->delete();
                });

                QuizSubmission::where('quiz_id', $lesson->id)->each(function ($submission) {
                    $submission->delete();
                });
            }

            // Delete the lesson
            $lesson->delete();
        }

        // Remove course files
        remove_file($course->thumbnail);
        remove_file($course->banner);
        remove_file($course->preview);

        // Delete the course
        $course->delete();

        // Redirect with success message
        return redirect(route('admin.courses'))->with('success', get_phrase('Course deleted successfully'));
    }

    public function draft($id)
    {
        $course = Course::where('id', $id)->first();
        if (!$course) {
            $response = [
                'error' => get_phrase('Data not found.'),
            ];
            return json_encode($response);
        }

        $status = $course->status == 'active' ? 'deactivate' : 'active';
        Course::where('id', $id)->update(['status' => $status]);
        $response = [
            'success' => get_phrase('Status has been updated.'),
        ];
        return json_encode($response);
    }

    public function duplicate($id)
    {
        $course = Course::where('id', $id);
        if (auth()->user()->role != 'admin') {
            $course = $course->where('user_id', auth()->user()->id);
        }

        // check course existence
        if ($course->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // collect course and remove unnecessary fields
        $data = $course->first()->toArray();
        if (auth()->user()->role == 'admin') {
            $data['user_id'] = auth()->user()->id;
        }
        unset($data['id'], $data['created_at'], $data['updated_at']);

        // insert as new course
        $course_id  = Course::insertGetId($data);
        Course::where('id', $course_id)->update(['slug' => slugify($data['title']).'-'.$course_id]);

        // go to edit
        Session::flash('success', get_phrase('Course duplicated.'));
        return redirect()->route('admin.course.edit', $course_id);
    }

    function approval(Request $request, $id)
    {
        Course::where('id', $id)->update(['status' => 'active']);

        $course = Course::where('id', $id)->first();

        config([
            'mail.mailers.smtp.transport'  => get_settings('protocol'),
            'mail.mailers.smtp.host'       => get_settings('smtp_host'),
            'mail.mailers.smtp.port'       => get_settings('smtp_port'),
            'mail.mailers.smtp.encryption' => get_settings('smtp_crypto'),
            'mail.mailers.smtp.username'   => get_settings('smtp_from_email'),
            'mail.mailers.smtp.password'   => get_settings('smtp_pass'),
            'mail.from.address'            => get_settings('smtp_from_email'),
            'mail.from.name'               => get_settings('smtp_user'),
        ]);

        $mail_data['subject']     = $request->subject;
        $mail_data['description'] = $request->message;

        try {
            Mail::to($course->creator->email)->send(new Mailer($mail_data));
        } catch (Exception $e) {
            
        }

        Session::flash('success', get_phrase('Course activated successfully'));
        return redirect()->route('admin.courses');
    }
}
