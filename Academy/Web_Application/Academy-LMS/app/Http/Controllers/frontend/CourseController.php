<?php

namespace App\Http\Controllers\frontend;

use App\Domain\Courses\Contracts\CourseCatalogReader;
use App\Domain\Courses\DTO\CourseFiltersData;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CourseController extends Controller
{
    public function __construct(private readonly CourseCatalogReader $courseCatalogReader)
    {
    }

    public function index(Request $request, $category = '')
    {
        $layout = Session::has('view') ? session('view') : 'grid';

        $result = $this->courseCatalogReader->fetchCatalog(
            CourseFiltersData::fromRequest($request, $category),
            $layout
        );

        $page_data = [
            'layout' => $result->layout,
            'courses' => $result->courses,
            'wishlist' => $result->wishlistCourseIds,
            'category_details' => $result->category,
        ];

        if ($result->parentCategorySlug) {
            $page_data['parent_cat'] = $result->parentCategorySlug;
        }

        if ($result->childCategorySlug) {
            $page_data['child_cat'] = $result->childCategorySlug;
        }

        return view('frontend' . '.' . get_frontend_settings('theme') . '.course.index', $page_data);
    }

    public function course_details(Request $request, $slug)
    {
        // validate slug
        if (empty($slug)) {
            return redirect()->back();
        }

        // course details
        $course = Course::where('slug', $slug)->where('status', 'active');
        if ($course->exists()) {
            $course_details              = $course->first();
            $page_data['course_details'] = $course_details;
            $page_data['sections']       = Section::where('course_id', $course_details->id)->orderBy('sort')->get();
            $page_data['total_lesson']   = Lesson::where('course_id', $course_details->id)->count();
            $page_data['enroll']         = Enrollment::where('course_id', $course_details->id)->count('user_id');

            $view_path = 'frontend.' . get_frontend_settings('theme') . '.course.course_details';
            return view($view_path, $page_data);
        } else {
            return redirect()->back();
        }
    }

    public function change_layout(Request $request)
    {
        $layout = Session::has('view');
        if ($layout) {
            Session::forget('view');
        }
        session(['view' => $request->view]);
        return response()->json(['reload' => true]);
    }

    // ------------------------------------------------------------------------------------------------------

    public function compare(Request $request)
    {
        $course_1 = $request->course_1;
        $course_2 = $request->course_2;
        $course_3 = $request->course_3;

        if (isset($course_1) && $course_1 != '') {
            $course_details_1              = Course::where('status', 'active')->where('slug', $course_1)->first();
            $page_data['course_details_1'] = $course_details_1;
        }
        if (isset($course_2) && $course_2 != '') {
            $course_details_2              = Course::where('status', 'active')->where('slug', $course_2)->first();
            $page_data['course_details_2'] = $course_details_2;
        }
        if (isset($course_3) && $course_3 != '') {
            $course_details_3              = Course::where('status', 'active')->where('slug', $course_3)->first();
            $page_data['course_details_3'] = $course_details_3;
        }

        if ($course_1 == '' && $course_2 == '' && $course_3 == '') {
            $page_data['course_details'] = '';
        }

        return view('frontend.course.compare', $page_data);
    }

    public function comparewith($course_1 = '', $course_2 = '', $course_3 = '')
    {

        $response = array();
        $result   = Course::join('users', 'courses.user_id', 'users.id')
            ->select('course.id as id', 'course.title as title', 'users.first_name as first_name', 'users.last_name as last_name')
            ->where('course.id', '!=', $course_1)
            ->where('course.id', '!=', $course_2)
            ->where('course.id', '!=', $course_3)
            ->where('course.status', 'active')
            
            ->like('course.title', $_GET['searchVal'])
            ->or_like('short_description', $_GET['searchVal'])
            ->or_like('first_name', $_GET['searchVal'])
            ->or_like('last_name', $_GET['searchVal'])
            
            ->take(100)
            ->get();

        foreach ($result as $key => $row) {
            $response[] = ['id' => $row->id, 'text' => $row->title . ' (' . get_phrase('Creator') . ': ' . $row->first_name];
        }
        echo json_encode($response);
    }
}
