<?php
namespace App\Models\payment_gateway;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Sslcommerz extends Model
{
    use HasFactory;

    public static function payment_status($identifier, $transaction_keys = [])
    {

        $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
        $keys            = json_decode($payment_gateway->keys, true);

        $val_id         = urlencode($_POST['val_id']);
        $store_key      = $keys['store_key'];
        $store_password = $keys['store_password'];

        if ($payment_gateway->test_mode == 1) {
            $store_key      = $keys['store_key'];
            $store_password = $keys['store_password'];
            $validation_url = "https://sandbox.sslcommerz.com";
        } else {
            $store_key      = $keys['store_live_key'];
            $store_password = $keys['store_live_password'];
            $validation_url = "https://securepay.sslcommerz.com";
        }

        $validation_url .= "/validator/api/validationserverAPI.php?val_id=" . $val_id . "&store_id=" . $store_key . "&store_passwd=" . $store_password . "&v=1&format=json";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $validation_url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && ! (curl_errno($handle))) {

            $result = json_decode($result, true);

            if ($result['status'] == 'VALIDATED' || $result['status'] == 'VALID') {

                return true;
            }
        }

        return false;

    }

    public static function payment_create($identifier)
    {
        $payment_details = session('payment_details');
        $payment_gateway = DB::table('payment_gateways')->where('identifier', 'sslcommerz')->first();
        $user            = DB::table('users')->where('id', auth()->user()->id)->first();
        $keys            = json_decode($payment_gateway->keys, true);

        $products_name = '';
        foreach ($payment_details['items'] as $key => $value):
            if ($key == 0) {
                $products_name .= $value['title'];
            } else {
                $products_name .= ', ' . $value['title'];
            }
        endforeach;

        if ($payment_gateway->test_mode == 1):
            $store_key      = $keys['store_key'];
            $store_password = $keys['store_password'];
            $direct_api_url = "https://sandbox.sslcommerz.com/gwprocess/v3/api.php";
        else:
            $store_key      = $keys['store_live_key'];
            $store_password = $keys['store_live_password'];
            $direct_api_url = "https://securepay.sslcommerz.com/gwprocess/v3/api.php";
        endif;

        $post_data                 = array();
        $post_data['user_id']      = $user->id;
        $post_data['payment_type'] = $identifier;
        $post_data['items_id']     = $payment_details['items'][0]['id'];
        $post_data['store_id']     = $store_key;
        $post_data['store_passwd'] = $store_password;
        $post_data['total_amount'] = round($payment_details['payable_amount']);
        $post_data['currency']     = "BDT";
        $post_data['tran_id']      = "SSLCZ_TXN_" . uniqid();
        $post_data['success_url']  = $payment_details['success_url'] . '/' . $payment_gateway->identifier;
        $post_data['fail_url']     = $payment_details['cancel_url'];
        $post_data['cancel_url']   = $payment_details['cancel_url'];

        # CUSTOMER INFORMATION
        $post_data['cus_name']     = $user->name;
        $post_data['cus_email']    = $user->email;
        $post_data['cus_add1']     = $user->address;
        $post_data['cus_city']     = "";
        $post_data['cus_state']    = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country']  = "";
        $post_data['cus_phone']    = $user->phone;
        $post_data['cus_fax']      = "";

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC

        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        $ssl_commerz_response = "";
        if ($code == 200 && ! (curl_errno($handle))) {
            curl_close($handle);
            $ssl_commerz_response = json_decode($content, true);
        } else {
            curl_close($handle);

            exit;
        }

        return $ssl_commerz_response['GatewayPageURL'];

    }

}
