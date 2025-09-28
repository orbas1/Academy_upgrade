<?php

namespace App\Models\payment_gateway;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Maxicash extends Model
{
    use HasFactory;

    public static function payment_status($identifier, $transaction_keys = [])
    {
        if ($transaction_keys != '') {
            array_shift($transaction_keys);
            session(['keys' => $transaction_keys]);
            return true;
        }
        return false;

    }

    public static function payment_create($identifier)
    {
        $identifier      = 'maxicash';
        $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
        $user            = DB::table('users')->where('id', auth()->user()->id)->first();
        $payment_details = session('payment_details');
        $keys            = json_decode($payment_gateway->keys, true);

        $products_name = '';
        foreach ($payment_details['items'] as $key => $value):
            if ($key == 0) {
                $products_name .= $value['title'];
            } else {
                $products_name .= ', ' . $value['title'];
            }
        endforeach;

        $merchant_id       = $keys['merchant_id'];
        $merchant_password = $keys['merchant_password'];

        $data1 = [
            "PayType"          => $identifier,
            "MerchantID"       => $merchant_id,
            "MerchantPassword" => $merchant_password,
            "Amount"           => (string) round($payment_details['payable_amount'] * 100),
            "Currency"         => $payment_gateway->currency,
            "Telephone"        => $user->phone,
            "Language"         => "en",
            "Reference"        => "MAXI_TXN_" . uniqid(),
            "accepturl"        => $payment_details['success_url'] . '/' . $payment_gateway->identifier,
            "declineurl"       => $payment_details['cancel_url'],
            "cancelurl"        => $payment_details['cancel_url'],
            "notifyurl"        => $payment_details['cancel_url'],
        ];

        $data = json_encode($data1);

        if ($payment_gateway->test_mode == 1) {
            $url = 'https://api-testbed.maxicashapp.com/payentry?data=' . $data;
        } else {
            $url = 'https://api.maxicashapp.com/payentry?data=' . $data;
        }

        return $url;

    }
}
