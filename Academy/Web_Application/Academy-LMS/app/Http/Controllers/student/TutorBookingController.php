<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\TutorBooking;
use App\Models\TutorSchedule;
use App\Models\TutorReview;
use App\Models\OfflinePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class TutorBookingController extends Controller
{
    public function my_bookings()
    {
        // Get the current timestamp for today at midnight
        $todayStart = strtotime('today');
        $todayEnd = strtotime('tomorrow') - 1;

        // Retrieve tutors with schedules starting within today
        $page_data['my_bookings'] = TutorBooking::where('student_id', auth()->user()->id)->where('start_time', '>=', $todayStart)->orderBy('id', 'desc')->paginate(10);

        $page_data['my_archive_bookings'] = TutorBooking::where('student_id', auth()->user()->id)->where('start_time', '<', $todayStart)->orderBy('id', 'desc')->paginate(10);

        $view_path = 'frontend.' . get_frontend_settings('theme') . '.student.my_bookings.index';
        return view($view_path, $page_data);
    }

    public function booking_invoice($id = "")
    {
        $page_data['booking'] = TutorBooking::find($id);
        $page_data['invoice'] = random(10);

        $view_path = 'frontend.' . get_frontend_settings('theme') . '.student.my_bookings.invoice';
        return view($view_path, $page_data);
    }
    

    //paymnet
    public function purchase($id)
    {
        $schedule = TutorSchedule::find($id);

        // check schedule owner
        if ($schedule->tutor_id == auth()->user()->id) {
            Session::flash('error', get_phrase('You own this schedule.'));
            return redirect()->back();
        }

        // check schedule is booked or not
        if (TutorBooking::where('student_id', auth()->user()->id)->where('schedule_id', $id)->exists()) {
            Session::flash('error', get_phrase('Schedule is already booked.'));
            return redirect()->back();
        }

        // check any offline processing data
        $processing_payments = OfflinePayment::where([
            'user_id'   => auth()->user()->id,
            'items'     => $schedule->id,
            'item_type' => 'tutor_booking',
            'status'    => 0,
        ])
            ->first();

        if ($processing_payments) {
            Session::flash('warning', get_phrase('Your request is in process.'));
            return redirect()->back();
        }

        // prepare team package payment data
        $payment_details = [
            'items'          => [
                [
                    'id'             => $schedule->id,
                    'title'          => $schedule->schedule_to_tutorCategory->name,
                    'subtitle'       => $schedule->schedule_to_tutorSubjects->name,
                    'price'          => $schedule->schedule_to_tutorCanTeach->price,
                    'discount_price' => '',
                ],
            ],

            'custom_field'   => [
                'item_type' => 'tutor_booking',
                'pay_for'   => get_phrase('Tutor Schedule Booking'),
            ],

            'success_method' => [
                'model_name'    => 'TutorBooking',
                'function_name' => 'purchase_schedule',
            ],

            'payable_amount' => round($schedule->schedule_to_tutorCanTeach->price, 2),
            'tax'            => 0,
            'cancel_url'     => route('tutor_schedule', [$schedule->tutor_id, slugify($schedule->schedule_to_tutor->name)]),
            'success_url'    => route('payment.success', ''),
        ];

        Session::put(['payment_details' => $payment_details]);
        return redirect()->route('payment');
    }

    public function join_class($booking_id = "")
    {
        $current_time  = time();
        $extended_time = $current_time + (60 * 15);

        $booking = TutorBooking::where('id', $booking_id)
            ->where('start_time', '<', $extended_time)
            ->where('student_id', auth()->user()->id)
            ->first();

        if (!empty($booking) && $current_time > $booking->end_time) {
            Session::flash('error', get_phrase('Time up! Session is over.'));
            return redirect()->route('my_bookings', ['tab' => 'live-upcoming']);
        }

        if (! $booking) {
            Session::flash('error', get_phrase('You can join the class 15 minutes before the class start or Session not found.'));
            return redirect()->route('my_bookings', ['tab' => 'live-upcoming']);
        }

        if(empty($booking->joining_data)) {
            $joining_info     = $this->create_zoom_meeting($booking->booking_to_schedule->schedule_to_tutorSubjects->name, $booking->start_time);

            $meeting_info = json_decode($joining_info, true);

            if (array_key_exists('code', $meeting_info) && $meeting_info) {
                return redirect()->back()->with('error', get_phrase($meeting_info['message']));
            }

            $data['joining_data'] = $joining_info;

            TutorBooking::where('id', $booking_id)->update($data);

            $booking->joining_data = $joining_info;

        }

        if (get_settings('zoom_web_sdk') == 'active') {
            $page_data['booking']   = $booking;
            $page_data['user']    = get_user_info($booking->student_id);
            $page_data['is_host'] = 0;

            $view_path = 'frontend.' . get_frontend_settings('theme') . '.student.my_bookings.join_tution';
            return view($view_path, $page_data);
        } else {
            $meeting_info = json_decode($booking->joining_data, true);
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

    public function tutor_review(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'tutor_id' => 'required|integer|exists:users,id',
        ]);

        $studentId = Auth::id();
        $tutorId = $request->input('tutor_id');

        // Check if a review already exists
        $existingReview = TutorReview::where('tutor_id', $tutorId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingReview) {
            // Update the existing review
            $existingReview->update([
                'rating' => $request->input('rating'),
                'review' => $request->input('review'),
            ]);
        } else {
            // Create a new review
            TutorReview::create([
                'tutor_id' => $tutorId,
                'student_id' => $studentId,
                'rating' => $request->input('rating'),
                'review' => $request->input('review'),
            ]);
        }

        return redirect()->back()->with('success', 'Review submitted successfully.');
    }
}
