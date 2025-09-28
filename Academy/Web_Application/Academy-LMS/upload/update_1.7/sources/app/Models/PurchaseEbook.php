<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PurchaseEbook extends Model
{
    use HasFactory;

    public static function purchase_ebook($identifier)
    {
        // get payment details
        $payment_details = session('payment_details');

        if (Session::has('keys')) {
            $transaction_keys          = session('keys');
            $transaction_keys          = json_encode($transaction_keys);
            $payment['transaction_id'] = $transaction_keys;
            $remove_session_item[]     = 'keys';
        }
        if (Session::has('session_id')) {
            $transaction_keys      = session('session_id');
            $payment['session_id'] = $transaction_keys;
            $remove_session_item[] = 'session_id';
        }

        // generate invoice for payment
        $payment['invoice'] = Str::random(20);
        for ($i = 0; $i < count($payment_details['items']); $i++) {

            $ebook_id = $payment_details['items'][$i]['id'];
            $creator_role = Ebook::join('users', 'ebooks.user_id', 'users.id')
                ->where('ebooks.id', $ebook_id)->value('users.role');

            if ($creator_role == 'admin') {
                $payment['admin_revenue'] = $payment_details['payable_amount'];
            } else {
                $payment['instructor_revenue'] = $payment_details['payable_amount'] * (get_settings('instructor_revenue') / 100);
                $payment['admin_revenue']      = $payment_details['payable_amount'] - $payment['instructor_revenue'];
            }
            $price                  = $payment_details['items'][$i]['price'];
            $ebook_discount         = $payment_details['items'][$i]['discount_price'];
            $payment['ebook_id']    = $payment_details['items'][$i]['id'];
            $payment['tax']          = $payment_details['tax'];
            $payment['amount']       = $ebook_discount ? $ebook_discount : $price;
            $payment['user_id']      = auth()->user()->id;
            $payment['payment_type'] = $identifier;
            $payment['status']       = 1;

            // insert payment history
            $payment_history = DB::table('ebook_purchases')->insert($payment);
        }

        $remove_session_item[] = 'payment_details';
        Session::forget($remove_session_item);
        Session::flash('success', 'Ebook purchsed successfully.');
        return redirect()->route('my.ebooks');
    }
}
