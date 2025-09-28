<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bootcamp;
use App\Models\BootcampPurchase;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\OfflinePayment;
use App\Models\Payment_history;
use App\Models\TeamPackagePurchase;
use App\Models\TeamTrainingPackage;
use App\Models\TutorBooking;
use App\Models\TutorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfflinePaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = OfflinePayment::orderBY('id', 'DESC');

        if ($request->status == 'approved') {
            $payments->where('status', 1);
        } elseif ($request->status == 'suspended') {
            $payments->where('status', 2);
        } elseif ($request->status == 'pending') {
            $payments->where('status', 0)->orWhere('status', null);
        }

        $page_data['payments'] = $payments->paginate(10);
        return view('admin.offline_payments.index', $page_data);
    }

    public function download_doc($id)
    {
        // validate id
        if (empty($id)) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // payment details
        $payment_details = OfflinePayment::where('id', $id)->first();
        $filePath        = public_path($payment_details->doc);
        if (! file_exists($filePath)) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }
        // download file
        return Response::download($filePath);
    }

    public function accept_payment($id)
    {
        // validate id
        if (empty($id)) {
            Session::flash('error', get_phrase('Id can not be empty.'));
            return redirect()->back();
        }

        // payment details
        $query = OfflinePayment::where('id', $id)->where('status', 0);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $payment_details = $query->first();

        $payment['invoice']      = Str::random(20);
        $payment['user_id']      = $payment_details['user_id'];
        $payment['payment_type'] = 'offline';
        $payment['coupon']       = $payment_details->coupon;

        if ($payment_details->item_type == 'course') {
            $items = json_decode($payment_details->items);
    
            foreach ($items as $item) {
                $course = Course::where('id', $item)->first();
                $payment['course_id'] = $course->id;
    
                // Calculate base amount
                $amount = $course->discount_flag == 1 ? $course->discounted_price : $course->price;
    
                // Apply coupon discount if applicable
                $discounted_price = 0;
                if ($payment_details->coupon) {
                    $coupon = Coupon::where('code', $payment_details->coupon)->first();
                    if ($coupon) {
                        $discounted_price = $amount * ($coupon->discount / 100);
                    }
                }
    
                // Final payment amount and tax
                $final_amount = $amount - $discounted_price;
                $payment['amount'] = $final_amount;
                $payment['tax'] = (get_settings('course_selling_tax') / 100) * $final_amount;
    
                // Calculate admin and instructor revenue
                if (get_course_creator_id($course->id)->role == 'admin') {
                    $payment['admin_revenue'] = $final_amount;
                } else {
                    $payment['instructor_revenue'] = $final_amount * (get_settings('instructor_revenue') / 100);
                    $payment['admin_revenue'] = $final_amount - $payment['instructor_revenue'];
                }
    
                // Insert payment record
                $accept_payment = Payment_history::insert($payment);
    
                // Enroll user in course if payment succeeds
                if ($accept_payment) {
                    
                    $enroll['user_id']         = $payment_details['user_id'];
                    $enroll['course_id']       = $course->id;
                    $enroll['enrollment_type'] = "paid";
                    $enroll['entry_date']      = time();
                    $enroll['created_at']      = date('Y-m-d H:i:s');
                    $enroll['updated_at']      = date('Y-m-d H:i:s');

                    if ($course->expiry_period > 0) {
                        $days = $course->expiry_period * 30;
                        $enroll['expiry_date'] = strtotime("+" . $days . " days");
                    } else {
                        $enroll['expiry_date'] = null;
                    }

                    Enrollment::insert($enroll);
                }
            }
        } elseif ($payment_details->item_type == 'bootcamp') {
            $bootcamps                           = Bootcamp::whereIn('id', json_decode($payment_details->items, true))->get();
            foreach($bootcamps as $bootcamp){
                $bootcamp_payment['invoice']        = '#' . Str::random(20);
                $bootcamp_payment['user_id']        = $payment_details['user_id'];
                $bootcamp_payment['bootcamp_id']    = $bootcamp->id;
                $bootcamp_payment['price']          = $bootcamp->discount_flag == 1 ? $bootcamp->price - $bootcamp->discounted_price : $bootcamp->price;
                $bootcamp_payment['tax']            = 0;
                $bootcamp_payment['payment_method'] = 'offline';
                $bootcamp_payment['status']         = 1;
    
                // insert bootcamp purchase
                BootcampPurchase::insert($bootcamp_payment);
            }
        } elseif ($payment_details->item_type == 'package') {
            $packages                           = TeamTrainingPackage::whereIn('id', json_decode($payment_details->items, true))->get();
            foreach($packages as $package){
                $package_payment['invoice']        = '#' . Str::random(20);
                $package_payment['user_id']        = $payment_details['user_id'];
                $package_payment['package_id']     = $package->id;
                $package_payment['price']          = $package->price;
                $package_payment['tax']            = 0;
                $package_payment['payment_method'] = 'offline';
                $package_payment['status']         = 1;
    
                // insert package purchase
                TeamPackagePurchase::insert($package_payment);
            }
        } elseif ($payment_details->item_type == 'tutor_booking') {
            $schedules                           = TutorSchedule::whereIn('id', json_decode($payment_details->items, true))->get();
            foreach($schedules as $schedule){

                $schedule_payment['invoice']        = '#' . Str::random(20);
                $schedule_payment['student_id']     = $payment_details['user_id'];
                $schedule_payment['schedule_id']    = $schedule->id;
                $schedule_payment['price']          = $payment_details['total_amount'];
                $schedule_payment['tax']            = $payment_details['tax'];
                $schedule_payment['payment_method'] = 'offline';

                $schedule = TutorSchedule::find($schedule->id);
                if (get_user_info($schedule->tutor_id)->role == 'admin') {
                    $schedule_payment['admin_revenue'] = $payment_details['payable_amount'];
                } else {
                    $schedule_payment['instructor_revenue'] = $payment_details['total_amount'] * (get_settings('instructor_revenue') / 100);
                    $schedule_payment['admin_revenue']      = $payment_details['total_amount'] - $schedule_payment['instructor_revenue'];
                }

                $schedule_payment['tutor_id'] = $schedule->tutor_id;
                $schedule_payment['start_time'] = $schedule->start_time;
                $schedule_payment['end_time'] = $schedule->end_time;
    
                // insert tutor bookings
                TutorBooking::insert($schedule_payment);
            }
        }

        // remove items from offline payment
        OfflinePayment::where('id', $id)->update(['status' => 1]);

        // go back
        Session::flash('success', 'Payment has been accepted.');
        return redirect()->route('admin.offline.payments');
    }

    public function decline_payment($id)
    {
        // remove items from offline payment
        OfflinePayment::where('id', $id)->update(['status' => 2]);

        // go back
        Session::flash('success', 'Payment has been suspended');
        return redirect()->route('admin.offline.payments');
    }

    public function delete_payment($id)
    {
        OfflinePayment::where('id', $id)->delete();
        Session::flash('success', get_phrase('Admin revenue delete successfully'));
        return redirect()->route('admin.offline.payments');
    }
}
