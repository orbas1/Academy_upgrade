<?php

namespace App\Models\payment_gateway;

use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class Doku extends Model
{
    use HasFactory;

    public static function payment_status($identifier, $bodyData = [], $headerData = [])
    {

        // print_r($bodyData);
        // print_r($headerData);

        $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
        $keys            = json_decode($payment_gateway->keys, true);
        $test_mode       = $payment_gateway->test_mode == 1 ? 1 : 0;

        if ($test_mode == 1) {
            $key        = $keys['public_test_key'];
            $secret_key = $keys['secret_test_key'];
        } else {
            $key        = $keys['public_live_key'];
            $secret_key = $keys['secret_live_key'];
        }

        // print_r($bodyData);

        if (count($bodyData) > 0) {
            // $notificationHeader = $headerData;
            // $notificationBody   = json_encode($bodyData);
            // $notificationPath   = '/payment-notification/doku';
            // // Adjust according to your notification path
            // $secretKey = $secret_key; // Adjust according to your secret key

            // $digest       = base64_encode(hash('sha256', $notificationBody, true));
            // $rawSignature = "Client-Id:" . $notificationHeader['Client-Id'] . "\n"
            //     . "Request-Id:" . $notificationHeader['Request-Id'] . "\n"
            //     . "Request-Timestamp:" . $notificationHeader['Request-Timestamp'] . "\n"
            //     . "Request-Target:" . $notificationPath . "\n"
            //     . "Digest:" . $digest;

            // $signature      = base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));
            // $finalSignature = 'HMACSHA256=' . $signature;

            // if ($finalSignature == $notificationHeader['Signature']) {

            //     $fileHandle = fopen('doku_success.txt', 'w');
            //     fwrite($fileHandle, 'Done');
            //     fclose($fileHandle);

            //     return true;

            // } else {

            //     return false;
            // }

            try {
                //Set session data
                $user = User::where('email', $bodyData['customer']['email'])->firstOrNew();
                Auth::login($user);

                $payment_details = json_decode($user->temp, true);
                if ($payment_details['expired_on'] >= time() && $bodyData['transaction']['status'] == 'SUCCESS') {
                    session(['payment_details' => $payment_details]);

                    $success_model    = $payment_details['success_method']['model_name'];
                    $success_function = $payment_details['success_method']['function_name'];
                    $model_full_path  = str_replace(' ', '', 'App\Models\ ' . $success_model);
                    $model_full_path::$success_function($identifier);

                    // Unset all session data
                    User::where('email', $bodyData['customer']['email'])->update(['temp' => json_encode([])]);
                    session(['payment_details' => []]);
                    Auth::logout();
                    return true;
                }
            } catch (\Exception $e) {
                // Log the error message
                Log::error('Doku error occurred: ' . $e->getMessage());

                // Optionally handle the exception (e.g., rethrow or show a custom error message)
                return response()->json(['error' => 'Something went wrong!'], 500);
            }
        } else {
            return "submitted";
        }
    }

    public static function storeTempData()
    {
        //Store payment data temporary
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'temp')) {
                $table->text('temp')->nullable();
            }
        });
        $payment_details               = session('payment_details');
        $payment_details['expired_on'] = time() + 300;
        User::where('id', auth()->user()->id)->update(['temp' => json_encode($payment_details)]);
        session(['payment_details' => []]);
    }
}
