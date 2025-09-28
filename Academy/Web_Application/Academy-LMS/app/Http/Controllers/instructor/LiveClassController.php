<?php

namespace App\Http\Controllers\instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Live_class;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{

    public function live_class_start($id)
    {
        $live_class = Live_class::where('id', $id)->first();

        if ($live_class->provider == 'zoom') {
            if (get_settings('zoom_web_sdk') == 'active') {
                return view('course_player.live_class.zoom_live_class', ['live_class' => $live_class]);
            } else {
                $meeting_info = json_decode($live_class->additional_info, true);
                return redirect($meeting_info['start_url']);
            }
        } else {
            return view('course_player.live_class.zoom_live_class', ['live_class' => $live_class]);
        }
    }

    public function live_class_store(Request $request, $course_id)
    {
        $validated = $request->validate([
            'class_topic'         => 'required|max:255',
            'class_date_and_time' => 'date|required',
            'user_id'             => 'required',
        ]);

        $data['class_topic']         = $request->class_topic;
        $data['course_id']           = $request->course_id;
        $data['user_id']             = $request->user_id;
        $data['provider']            = $request->provider;
        $data['class_date_and_time'] = date('Y-m-d\TH:i:s', strtotime($request->class_date_and_time));
        $data['note']                = $request->note;

        if ($request->provider == 'zoom') {
            $meeting_info     = $this->create_zoom_live_class($request->class_topic, $request->class_date_and_time);
            $meeting_info_arr = json_decode($meeting_info, true);
            if (array_key_exists('code', $meeting_info_arr) && $meeting_info_arr) {
                return redirect(route('instructor.course.edit', ['id' => $course_id, 'tab' => 'live-class']))->with('error', get_phrase($meeting_info_arr['message']));
            }
            $data['additional_info'] = $meeting_info;
        }
        Live_class::insert($data);

        return redirect(route('instructor.course.edit', ['id' => $course_id, 'tab' => 'live-class']))->with('success', get_phrase('Live class added successfully'));
    }

    public function live_class_update(Request $request, $id)
    {
        $previous_meeting_data = Live_class::where('id', $id)->first();

        $request->validate([
            'class_topic'         => 'required|max:255',
            'class_date_and_time' => 'date|required',
            'user_id'             => 'required',
        ]);

        $data['class_topic']         = $request->class_topic;
        $data['user_id']             = $request->user_id;
        $data['class_date_and_time'] = date('Y-m-d\TH:i:s', strtotime($request->class_date_and_time));
        $data['note']                = $request->note;

        if ($previous_meeting_data->provider == 'zoom') {
            $previous_meeting_info = json_decode($previous_meeting_data->additional_info, true);
            $this->update_zoom_live_class($request->class_topic, $request->class_date_and_time, $previous_meeting_info['id']);
            $previous_meeting_info["start_time"] = date('Y-m-d\TH:i:s', strtotime($request->class_date_and_time));
            $previous_meeting_info["topic"]      = $request->class_topic;
            $data['additional_info']             = json_encode($previous_meeting_info);
        }
        Live_class::where('id', $id)->update($data);

        return redirect(route('instructor.course.edit', ['id' => $previous_meeting_data->course_id, 'tab' => 'live-class']))->with('success', get_phrase('Live class updated successfully'));
    }

    public function live_class_delete($id)
    {
        $previous_meeting_data = Live_class::where('id', $id)->first();
        $course                = Course::where('id', $previous_meeting_data->course_id)->first();

        if ($course->instructors()->count() > 0) {
            $previous_meeting_info = json_decode($previous_meeting_data->additional_info, true);
            $this->delete_zoom_live_class($previous_meeting_info['id']);
            Live_class::where('id', $id)->delete();
        }

        return redirect(route('instructor.course.edit', ['id' => $previous_meeting_data->course_id, 'tab' => 'live-class']))->with('success', get_phrase('Live class deleted successfully'));
    }

    public function live_class_settings()
    {
        return view('instructor.setting.live_class_settings');
    }

    public function update_live_class_settings(Request $request)
    {
        $validated = $request->validate([
            'zoom_account_email' => 'required:email',
            'zoom_web_sdk'       => 'required|in:active,inactive',
            'zoom_account_id'    => 'required',
            'zoom_client_id'     => 'required',
            'zoom_client_secret' => 'required',
        ]);

        foreach ($request->all() as $name => $value) {
            if (Setting::where('type', $name)->count() > 0) {
                Setting::where('type', $name)->update(['description' => $value]);
            } else {
                Setting::insert(['type' => $name, 'description' => $value]);
            }
        }

        return redirect(route('instructor.live.class.settings'))->with('success', get_phrase('Zoom live class settings has been configured'));
    }

    public function create_zoom_live_class($topic, $date_and_time)
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

    public function update_zoom_live_class($topic, $date_and_time, $meetingId)
    {
        $token = $this->create_zoom_token(); // Obtain the access token

        // API Endpoint for updating a meeting
        $zoomEndpoint = 'https://api.zoom.us/v2/meetings/' . $meetingId;

        // Meeting data with updated start time
        $meetingData = [
            'topic'      => $topic,
            'start_time' => date('Y-m-d\TH:i:s', strtotime($date_and_time)), // New start time (in UTC)
        ];

        // Prepare headers
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        // Make PATCH request to update meeting
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zoomEndpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meetingData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        // JSON response
        return $response;
    }

    public function delete_zoom_live_class($meetingId)
    {
        $token = $this->create_zoom_token(); // Obtain the access token

        // API Endpoint for deleting a meeting
        $zoomEndpoint = 'https://api.zoom.us/v2/meetings/' . $meetingId;

        // Prepare headers
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        // Make DELETE request to delete meeting
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zoomEndpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
