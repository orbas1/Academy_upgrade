<?php

namespace App\Http\Controllers\instructor;

use App\Http\Controllers\Controller;
use App\Models\TutorCategory;
use App\Models\TutorSubject;
use App\Models\TutorBooking;
use App\Models\TutorSchedule;
use App\Models\TutorCanTeach;
use App\Models\FileUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;  // Import Carbon class
use Carbon\CarbonPeriod;  // Import CarbonPeriod for date ranges

class TutorBookingController extends Controller
{

    public function my_subjects()
    {
        // Fetch all records for the instructor
        $categories = TutorCanTeach::where('instructor_id', auth()->user()->id)
            ->with('category_to_tutorCategory')  // Eager load the related category
            ->get();

        // Filter out duplicates by category_id
        $page_data['categories'] = $categories->unique('category_id');
        return view('instructor.tutor_booking.my_subjects', $page_data);
    }

    public function my_subject_add()
    {}

    public function my_subject_store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required',
            'subject_id' => 'required',
            'description' => 'required',
            'price' => 'required'
        ]);

        $data['category_id'] = $request->category_id;
        $data['subject_id'] = $request->subject_id;
        $data['instructor_id'] = auth()->user()->id;
        $data['description'] = $request->description;
        $data['price'] = $request->price;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($request->thumbnail) {
            $data['thumbnail'] = "uploads/tutor-booking/subject-thumbnail/" . nice_file_name(random(9), $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $data['thumbnail'], 400, null, 200, 200);
        }

        TutorCanTeach::insert($data);

        return redirect(route('instructor.my_subjects'))->with('success', get_phrase('Subject added successfully'));
    }

    public function my_subject_edit()
    {}

    public function my_subject_update(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id' => 'required',
            'subject_id' => 'required',
            'description' => 'required',
            'price' => 'required'
        ]);

        // Fetch the existing record
        $subject = TutorCanTeach::findOrFail($id);

        // Prepare the data for update
        $data['category_id'] = $request->category_id;
        $data['subject_id'] = $request->subject_id;
        $data['instructor_id'] = auth()->user()->id;
        $data['description'] = $request->description;
        $data['price'] = $request->price;
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle thumbnail upload
        if ($request->thumbnail) {
            // Unlink the previous thumbnail if it exists
            if ($subject->thumbnail && file_exists(public_path($subject->thumbnail))) {
                unlink(public_path($subject->thumbnail));
            }

            // Generate a new thumbnail path and upload the new file
            $data['thumbnail'] = "uploads/tutor-booking/subject-thumbnail/" . nice_file_name(random(9), $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $data['thumbnail'], 400, null, 200, 200);
        }

        // Update the record
        TutorCanTeach::where('id', $id)->update($data);

        // Redirect back with success message
        return redirect(route('instructor.my_subjects'))->with('success', get_phrase('Subject updated successfully'));
    }

    public function my_subject_delete($id)
    {
        $query = TutorCanTeach::where('id', $id);
        $query->delete();

        return redirect(route('instructor.my_subjects'))->with('success', get_phrase('Subject deleted successfully'));
    }

    public function my_subject_category_delete($id)
    {
        $subjects = TutorCanTeach::where('category_id', $id)->get();

        if ($subjects->isEmpty()) {
            return redirect()->back()->with('error', get_phrase('No subjects found for this category'));
        }

        // Delete all records associated with the category_id
        TutorCanTeach::where('category_id', $id)->delete();

        // Redirect with a success message
        return redirect(route('instructor.my_subjects'))->with('success', get_phrase('Subjects for the selected category deleted successfully'));
    }

    public function manage_schedules()
    {
        // Retrieve the schedules for the authenticated tutor
        $schedule_list = TutorSchedule::where('tutor_id', auth()->user()->id)->get();

        $schedulesByDate = [];

        foreach ($schedule_list as $schedule) {
            // Get the formatted date (Y-m-d) from the start time
            $date = date('Y-m-d', $schedule->start_time);

            // Group schedules by date
            if (!isset($schedulesByDate[$date])) {
                $schedulesByDate[$date] = 0;
            }
            
            // Increment count for the specific date
            $schedulesByDate[$date]++;
        }

        $schedules = [];

        foreach ($schedulesByDate as $date => $count) {
            // Create an info array for each unique date with schedule count
            $info = [
                'title' => $count . ' schedules', // Display the count of schedules
                'start' => $date, // Use the date as start
            ];

            $schedules[] = $info;
        }

        // Convert the schedules array to JSON
        $schedules = json_encode($schedules);

        // Pass the schedules to the view
        return view('instructor.tutor_booking.manage_schedules', ['schedules' => $schedules]);
    }

    public function manage_schedules_by_date($date)
    {
        // Parse the provided date to match the expected format (e.g., '12-Feb-24')
        $parsedDate = \DateTime::createFromFormat('d-M-y', $date);

        if (!$parsedDate) {
            Session::flash('error', get_phrase('Invalid date format'));
            return redirect()->back();
        }

        $formattedDate = $parsedDate->format('Y-m-d');

        // Get the start and end timestamps for the selected day
        $dayStart = $parsedDate->setTime(0, 0)->getTimestamp();
        $dayEnd = $parsedDate->setTime(23, 59, 59)->getTimestamp();

        // Retrieve schedules for the authenticated tutor within the specified day's timestamp range
        $schedules = TutorSchedule::where('tutor_id', auth()->user()->id)
                        ->where('start_time', '>=', $dayStart)
                        ->where('start_time', '<=', $dayEnd)
                        ->paginate(10);

       
        return view('instructor.tutor_booking.schedules_by_day', ['schedules' => $schedules, 'selected_date' => $date]);
    }

    public function schedule_edit($id = "")
    {
        $page_data['schedule_details'] = TutorSchedule::find($id);
        // Fetch all records for the instructor
        $categories = TutorCanTeach::where('instructor_id', auth()->user()->id)
            ->with('category_to_tutorCategory')  // Eager load the related category
            ->get();

        // Filter out duplicates by category_id
        $page_data['categories'] = $categories->unique('category_id');
        return view('instructor.tutor_booking.edit_schedule', $page_data);
    }

    public function schedule_update(Request $request, $id)
    {
        // Validate the form input
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'start_time' => 'required|date',
            'duration' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        // Cast duration to an integer just in case
        $duration = (int) $validated['duration'];

        // Start time for the session
        $startDate = Carbon::parse($validated['start_time']);

        // Save single session to TutorSchedule
        TutorSchedule::where('id', $id)->update([
            'category_id' => $validated['category_id'],
            'subject_id' => $validated['subject_id'],
            'start_time' => $startDate->timestamp,  // Store as timestamp
            'end_time' => $startDate->copy()->addMinutes($duration)->timestamp,
            'duration' => $duration,
            'description' => $validated['description'],
        ]);

        // Extract the date in the desired format (e.g., 12-Feb-24)
        $date = $startDate->format('d-M-y');

        return redirect()->route('instructor.manage_schedules_by_date', ['date' => $date])->with('success', get_phrase('Schedule updated successfully.'));
    
    }

    public function schedule_delete($id)
    {
        // Retrieve the schedule record
        $schedule = TutorSchedule::find($id);

        if (!$schedule) {
            return redirect()->back()->with('error', get_phrase('No schedule found for this id'));
        }

        // Delete the schedule
        $schedule->delete();

        // Redirect to the route with the formatted date
        return redirect()->back()->with('success', get_phrase('Schedule deleted successfully.'));
    }


    public function add_schedule()
    {
        // Fetch all records for the instructor
        $categories = TutorCanTeach::where('instructor_id', auth()->user()->id)
            ->with('category_to_tutorCategory')  // Eager load the related category
            ->get();

        // Filter out duplicates by category_id
        $page_data['categories'] = $categories->unique('category_id');
        return view('instructor.tutor_booking.add_schedule', $page_data);
    }

    public function schedule_store(Request $request)
    {
        // Validate the form input
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'tution_type' => 'required|integer',  // 1 for single, 0 for repeated
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',  // Only needed for repeated sessions
            'duration' => 'required|integer',
            'description' => 'nullable|string',
            '1_day' => 'nullable|array',  // Only for repeated schedule (array of selected days)
        ]);

        // Cast duration to an integer just in case
        $duration = (int) $validated['duration'];

        // Start time for the session
        $startDate = Carbon::parse($validated['start_time']);

        if ($validated['tution_type'] == 0) {  // Repeated session (tution_type == 0)
            if (!empty($validated['end_time'])) {
                $endDate = Carbon::parse($validated['end_time']);

                // Create a period between start and end dates
                $period = CarbonPeriod::create($startDate, $endDate);

                // Check if specific days are selected for the repeated schedule
                if (!empty($validated['1_day'])) {
                    foreach ($period as $date) {
                        $dayOfWeek = strtolower($date->format('l'));
                        if (in_array($dayOfWeek, $validated['1_day'])) {
                            // Save each repeated session to TutorSchedule
                            TutorSchedule::create([
                                'tutor_id' => auth()->user()->id,
                                'category_id' => $validated['category_id'],
                                'subject_id' => $validated['subject_id'],
                                'start_time' => $date->timestamp,  // Store as timestamp
                                'end_time' => $date->copy()->addMinutes($duration)->timestamp,
                                'duration' => $duration,
                                'description' => $validated['description'],
                                'tution_type' => $validated['tution_type'],
                            ]);
                        }
                    }
                }
            }
        } else {  // Single session (tution_type == 1)
            // Save single session to TutorSchedule
            TutorSchedule::create([
                'tutor_id' => auth()->user()->id,
                'category_id' => $validated['category_id'],
                'subject_id' => $validated['subject_id'],
                'start_time' => $startDate->timestamp,  // Store as timestamp
                'end_time' => $startDate->copy()->addMinutes($duration)->timestamp,
                'duration' => $duration,
                'description' => $validated['description'],
                'tution_type' => $validated['tution_type'],
            ]);
        }

        return redirect(route('instructor.manage_schedules'))->with('success', get_phrase('Schedule successfully created.'));
    }

    public function subject_by_category_id(Request $request)
    {
        if (isset($request->category_id)) {
            $teaches = TutorCanTeach::where('category_id', $request->category_id)->get();
            return view('instructor.tutor_booking.load_subjects', compact('teaches'));
        }
    }

    public function tutor_booking_list()
    {
        // Get the current timestamp for today at midnight
        $todayStart = strtotime('today');

        // Initialize query for current bookings (today onwards)
        $currentQuery = TutorBooking::where('tutor_bookings.start_time', '>=', $todayStart)
            ->join('tutor_schedules', 'tutor_bookings.schedule_id', '=', 'tutor_schedules.id')
            ->join('tutor_subjects', 'tutor_schedules.subject_id', '=', 'tutor_subjects.id')
            ->join('users', 'tutor_bookings.student_id', '=', 'users.id')
            ->select('tutor_bookings.*', 'tutor_subjects.name', 'users.name as student_name')
            ->orderBy('tutor_bookings.id', 'desc');

        // Initialize query for archived bookings (before today)
        $archiveQuery = TutorBooking::where('tutor_bookings.start_time', '<', $todayStart)
            ->join('tutor_schedules', 'tutor_bookings.schedule_id', '=', 'tutor_schedules.id')
            ->join('tutor_subjects', 'tutor_schedules.subject_id', '=', 'tutor_subjects.id')
            ->join('users', 'tutor_bookings.student_id', '=', 'users.id')
            ->select('tutor_bookings.*', 'tutor_subjects.name', 'users.name as student_name')
            ->orderBy('tutor_bookings.id', 'desc');

        // Add search condition for both queries
        if (request()->has('search')) {
            $search = request()->query('search');
            $currentQuery = $currentQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', "%{$search}%")
                    ->orWhere('tutor_subjects.name', 'LIKE', "%{$search}%");
            });

            $archiveQuery = $archiveQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'LIKE', "%{$search}%")
                    ->orWhere('tutor_subjects.name', 'LIKE', "%{$search}%");
            });
        }

        // Paginate results
        $page_data['booking_list'] = $currentQuery->paginate(20)->appends(request()->query());
        $page_data['archive_list'] = $archiveQuery->paginate(20, ['*'], 'archive_page')->appends(request()->query());

        // Return the view with the data
        return view('instructor.tutor_booking.tutor_booking_list', $page_data);
    }

    public function join_class($booking_id = "")
    {
        $booking_details = TutorBooking::find($booking_id);
        
        if(empty($booking_details->joining_data)) {
            $joining_info     = $this->create_zoom_meeting($booking_details->booking_to_schedule->schedule_to_tutorSubjects->name, $booking_details->start_time);

            $meeting_info = json_decode($joining_info, true);

            if (array_key_exists('code', $meeting_info) && $meeting_info) {
                return redirect()->back()->with('error', get_phrase($meeting_info['message']));
            }

            $data['joining_data'] = $joining_info;

            TutorBooking::where('id', $booking_id)->update($data);

        } else {
            $meeting_info = json_decode($booking_details->joining_data, true);
        }

        $current_time  = time();
        $extended_time = $current_time + (60 * 15);

        $booking = TutorBooking::where('id', $booking_id)
            ->where('start_time', '<', $extended_time)
            ->where('end_time', '>', $current_time)
            ->where('tutor_id', auth()->user()->id)
            ->first();

        if (! $booking) {
            Session::flash('error', get_phrase('Session not found.'));
            return redirect()->back();
        }

        if (get_settings('zoom_web_sdk') == 'active') {
            $page_data['booking']   = $booking;
            $page_data['user']    = get_user_info($booking->tutor_id);
            $page_data['is_host'] = 1;
            return view('instructor.tutor_booking.join_tution', $page_data);
        } else {
            return redirect($meeting_info['start_url']);
        }
    }

    public function create_zoom_meeting($topic, $date_and_time)
    {
        $zoom_account_email = get_settings('zoom_account_email');
        $token              = $this->create_zoom_token();
        // API Endpoint for creating a meeting
        $zoomEndpoint = 'https://api.zoom.us/v2/users/me/meetings';

        // Meeting data
        $meetingData = [
            'topic'        => $topic,
            'schedule_for' => $zoom_account_email,
            'type'         => 2, // Scheduled meeting
            'start_time' => date('Y-m-d\TH:i:s', strtotime($date_and_time)), // Start time (in UTC)
            'duration' => 60, // Duration in minutes
            'timezone' => get_settings('timezone'), // Timezone
            'settings' => [
                'approval_type'    => 2,
                'join_before_host' => true,
                'jbh_time'         => 0,
            ],
        ];
        // Prepare headers
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        // Make POST request to create meeting
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zoomEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meetingData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        // JSON response
        return $response;
    }

    public function create_zoom_token()
    {
        // Access the environment variables
        $clientId     = get_settings('zoom_client_id');
        $clientSecret = get_settings('zoom_client_secret');
        $accountId    = get_settings('zoom_account_id');
        $oauthUrl     = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $accountId; // Replace with your OAuth endpoint URL
        $authHeader = 'Basic ' . base64_encode($clientId . ':' . $clientSecret);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $accountId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: ' . $authHeader,
                'Cookie: __cf_bm=kn5ec2sntPlDgQWVWtSYdPeISb6bp6F7NHwuAFJQaGk-1736318967-1.0.1.1-SeM4z5pIb.nrMno35jg3y5BdXib.GPP13s2_yNnqz6LxbU9zsFTfZQc3a_Di94qx3DoyQjBT2Osz7idFMWWOrw; _zm_chtaid=816; _zm_ctaid=nbT15WlMTsCEAgCyUg3rYw.1736318967206.63747957ef3128f771210fb69e8d6831; _zm_mtk_guid=fa77ae1ac8e349bea690b72d942d6de6; _zm_page_auth=us04_c_uqPDVOshQQK6FRkhQPiPRA; _zm_ssid=us04_c_atJ4QboaQZG4Lw4U-Wpx6A; cred=A404810ADF33AFC21FF517216A4CB862'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $oauthResponse = json_decode($response, true) ?? ['access_token' => ''];
        $accessToken   = $oauthResponse['access_token'];
        return $accessToken;
    }

}
