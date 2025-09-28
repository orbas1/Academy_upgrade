<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TutorSchedule;
use App\Models\TutorCategory;
use App\Models\TutorSubject;
use App\Models\TutorReview;
use App\Models\TutorCanTeach;
use Illuminate\Http\Request;

class TutorBookingController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve distinct tutor_ids from TutorSchedule
        $tutorIds = TutorSchedule::select('tutor_id')->distinct()->pluck('tutor_id');

        // Convert to an array if needed
        $tutorIdsArray = $tutorIds->toArray();

        // Initialize the query to get users based on tutor_ids
        $query = User::whereIn('id', $tutorIdsArray);

        if ($request->has('search')) {

            $query->where('name', 'LIKE', '%' . request()->input('search') . '%');
        }

        // Check if a category & subject are present in the request
        if ($request->has('category') && $request->has('subject')) {
            $categorySlug = $request->input('category');
            $subjectSlug = $request->input('subject');

            // Get category_id based on category
            $categoryId = TutorCategory::where('slug', $categorySlug)->value('id');

            // Get subject_id based on subject
            $subjectId = TutorSubject::where('slug', $subjectSlug)->value('id');

            // Filter TutorSchedule by both category_id and subject_id
            $tutorIdsByCategoryAndSubject = TutorSchedule::where('category_id', $categoryId)
                ->where('subject_id', $subjectId)
                ->pluck('tutor_id');

            // Intersect the filtered tutor IDs with the existing filtered IDs
            $filteredTutorIds = array_intersect($tutorIdsArray, $tutorIdsByCategoryAndSubject->toArray());

        } else {
            $filteredTutorIds = $tutorIdsArray; // Initialize filteredTutorIds with all tutor IDs

            // Check if a category is present in the request
            if ($request->has('category')) {
                $categorySlug = $request->input('category');

                // Get category_id based on category
                $categoryId = TutorCategory::where('slug', $categorySlug)->value('id');

                // Filter TutorSchedule by category_id
                $tutorIdsByCategory = TutorSchedule::where('category_id', $categoryId)
                    ->pluck('tutor_id');

                // Intersect the filtered tutor IDs with the existing filtered IDs
                $filteredTutorIds = array_intersect($filteredTutorIds, $tutorIdsByCategory->toArray());
            }

            // Check if a subject is present in the request
            if ($request->has('subject')) {
                $subjectSlug = $request->input('subject');

                // Get subject_id based on subject
                $subjectId = TutorSubject::where('slug', $subjectSlug)->value('id');

                // Filter TutorSchedule by subject_id
                $tutorIdsBySubject = TutorSchedule::where('subject_id', $subjectId)
                    ->pluck('tutor_id');

                // Intersect the filtered tutor IDs with the existing filtered IDs
                $filteredTutorIds = array_intersect($filteredTutorIds, $tutorIdsBySubject->toArray());
            }
        }

        if ($request->has('min_fee') && $request->has('max_fee')) {
            $minFee = $request->input('min_fee');
            $maxFee = $request->input('max_fee');

            $tutorIdsByPrice = TutorCanTeach::where('price', '>=', $minFee)
                        ->where('price', '<=', $maxFee)
                        ->pluck('instructor_id');

            // If no tutor IDs found by rating, clear the existing tutor IDs array
            if ($tutorIdsByPrice->isEmpty()) {
                $filteredTutorIds = []; // Clear the array to ensure no tutors are returned
            } else {
                // Merge the filtered tutor IDs with the original tutor IDs
                $filteredTutorIds = array_intersect($filteredTutorIds, $tutorIdsByPrice->toArray());
            }
        }

        // Check if a rating is present in the request
        if ($request->has('rating')) {
            $rating = $request->input('rating');

            // Get tutor_ids with average ratings matching the requested rating
            $tutorIdsByRating = TutorReview::select('tutor_id')
                        ->groupBy('tutor_id')
                        ->havingRaw('AVG(rating) = ?', [$rating])
                        ->pluck('tutor_id');

            // If no tutor IDs found by rating, clear the existing tutor IDs array
            if ($tutorIdsByRating->isEmpty()) {
                $filteredTutorIds = []; // Clear the array to ensure no tutors are returned
            } else {
                // Merge the filtered tutor IDs with the original tutor IDs
                $filteredTutorIds = array_intersect($filteredTutorIds, $tutorIdsByRating->toArray());
            }
        }

        // Retrieve the filtered users
        $page_data['tutors'] = $query->whereIn('id', $filteredTutorIds)->paginate(10)->appends(request()->query());
        $page_data['categories'] = TutorCategory::where('status', 1)->get();
        $page_data['subjects'] = TutorSubject::where('status', 1)->get();

        $view_path = 'frontend' . '.' . get_frontend_settings('theme') . '.tutor_booking.index';
        return view($view_path, $page_data);

    }

    public function index2(Request $request)
    {

        $query = TutorSchedule::query();

        // Filter by category if specified
        if (request()->has('category')) {
            $category_details = TutorCategory::where('slug', $request->category)->first();
            if ($category_details) {
                $query = $query->where('category_id', $category_details->id);
            }
        }

        // filter by subject
        if (request()->has('subject')) {
            $subject = request()->query('subject');
            $subject_details = TutorSubject::where('slug', $subject)->first();
            if ($subject_details) {
                $query = $query->where('subject_id', $subject_details->id);
            }
        }

        // filter by rating
        if (request()->has('rating')) {
            $rating = request()->query('rating');

            // Calculate average ratings for tutors and get IDs of those with matching average
            $tutor_ids = TutorReview::select('tutor_id')
                        ->groupBy('tutor_id')
                        ->havingRaw('AVG(rating) = ?', [$rating])
                        ->pluck('tutor_id');
            
            // if ($tutor_ids->isNotEmpty()) {
            //     $query = $query->whereIn('tutor_id', $tutor_ids);
            // }
            $query = $query->whereIn('tutor_id', $tutor_ids);
        }

        // Get unique tutor IDs first, then paginate
        $uniqueTutorSchedules = $query->select('tutor_id')
                                    ->distinct()
                                    ->paginate(20)
                                    ->appends(request()->query());

        // Load the categories and subjects
        $page_data['tutors'] = $uniqueTutorSchedules;
        $page_data['categories'] = TutorCategory::where('status', 1)->get();
        $page_data['subjects'] = TutorSubject::where('status', 1)->get();

        $view_path = 'frontend' . '.' . get_frontend_settings('theme') . '.tutor_booking.index';
        return view($view_path, $page_data);
    }

    public function tutor_schedule2(Request $request, $user)
    {
        $page_data['tutor_details'] = User::find($request->tutor_id);

        // Get the current timestamp for today at midnight
        $todayStart = strtotime('today'); // This gives you the timestamp for today at 00:00:00

        // Retrieve tutors with schedules starting from today onwards
        $page_data['schedules'] = TutorSchedule::where('start_time', '>=', $todayStart)->get();

        $page_data['reviews'] = TutorReview::where('tutor_id', $request->tutor_id)->get();
        $view_path                  = 'frontend' . '.' . get_frontend_settings('theme') . '.tutor_booking.tutor_schedule';
        return view($view_path, $page_data);
    }

    public function tutor_schedule(Request $request, $id, $user)
    {
        // Find the tutor details based on the given ID
        $page_data['tutor_details'] = User::find($id);

        // Get the current timestamp for today at midnight
        $todayStart = strtotime('today');
        $todayEnd = strtotime('tomorrow') - 1;

        // Retrieve tutors with schedules starting and ending within today
        $page_data['schedules'] = TutorSchedule::where('tutor_id', $id)
            ->where('start_time', '>=', $todayStart)
            ->where('end_time', '<=', $todayEnd)
            ->get();

        // Generate date data for the Swiper calendar
        $page_data['dateSwiperData'] = [];
        $today = new \DateTime();
        $twoYearsFromNow = (clone $today)->modify('+2 years');

        while ($today <= $twoYearsFromNow) {
            $day = $today->format('d');
            $month = $today->format('M');
            $year = $today->format('Y');
            $dayName = $today->format('D');

            // Check if the date is today
            $isToday = $today->format('Y-m-d') === (new \DateTime())->format('Y-m-d');

            $page_data['dateSwiperData'][] = [
                'day' => $day,
                'month' => $month,
                'year' => $year,
                'dayName' => $dayName,
                'isToday' => $isToday
            ];

            // Move to the next day
            $today->modify('+1 day');
        }

        // Get reviews for the specified tutor
        $page_data['reviews'] = TutorReview::where('tutor_id', $id)->get();

        // Define the view path based on frontend settings
        $view_path = 'frontend' . '.' . get_frontend_settings('theme') . '.tutor_booking.tutor_schedule';

        // Return the view with the prepared data
        return view($view_path, $page_data);
    }

    public function getSchedulesForDate($date, $tutor_id)
    {
        // Convert the date to a timestamp for the beginning and end of the day
        $startOfDay = strtotime($date);
        $endOfDay = strtotime('+1 day', $startOfDay) - 1;

        // Retrieve schedules that fall within the specified date
        $schedules = TutorSchedule::where('tutor_id', $tutor_id)
            ->where('start_time', '>=', $startOfDay)
            ->where('end_time', '<=', $endOfDay)
            ->get();

        // Return the partial view with schedules
        return view('frontend.default.tutor_booking.schedules', compact('schedules'));
    }

    public function getSchedulesByCalenderDate($date, $tutor_id)
    {
        // Convert the date to a timestamp for the beginning and end of the day
        $startOfDay = strtotime($date);
        $endOfDay = strtotime('+1 day', $startOfDay) - 1;

        // Retrieve schedules that fall within the specified date
        $page_data['schedules'] = TutorSchedule::where('tutor_id', $tutor_id)
            ->where('start_time', '>=', $startOfDay)
            ->where('end_time', '<=', $endOfDay)
            ->get();

        // Generate date data for the Swiper calendar
        $page_data['dateSwiperData'] = [];
        $slectedDay = new \DateTime($date);  // Start from the selected date
        $twoYearsFromNow = (clone $slectedDay)->modify('+2 years');

        while ($slectedDay <= $twoYearsFromNow) {
            $day = $slectedDay->format('d');
            $month = $slectedDay->format('M');
            $year = $slectedDay->format('Y');
            $dayName = $slectedDay->format('D');

            // Check if the date is the selected day
            $isToday = $slectedDay->format('Y-m-d') === (new \DateTime($date))->format('Y-m-d');

            $page_data['dateSwiperData'][] = [
                'day' => $day,
                'month' => $month,
                'year' => $year,
                'dayName' => $dayName,
                'isToday' => $isToday
            ];

            // Move to the next day
            $slectedDay->modify('+1 day');
        }

        // Return the partial view with schedules
        return view('frontend.default.tutor_booking.schedules_tab', $page_data);
    }


    
}
