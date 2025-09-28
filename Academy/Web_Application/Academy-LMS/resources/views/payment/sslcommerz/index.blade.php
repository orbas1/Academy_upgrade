@php
    $model = $payment_details['success_method']['model_name'];

    if ($model == 'InstructorPayment') {
        $payment_keys = DB::table('users')
            ->where('id', $payment_details['items'][1]['id'])
            ->value('paymentkeys');

        $keys = isset($payment_keys) ? json_decode($payment_keys) : null;

        if ($payment_gateway->test_mode == 1) {
            $store_key = $store_live_key = '';
            if ($keys) {
                $key = $keys->sslcommerz->store_key;
                $key = $keys->sslcommerz->store_live_key;
            }
        } else {
            $store_password = $store_live_password = '';
            if ($keys) {
                $key = $keys->sslcommerz->store_live_password;
                $key = $keys->sslcommerz->store_password;
            }
        }

        if ($key == '') {
            $msg = get_phrase('This payment gateway is not configured.');
        }
    } else {
        $payment_keys = json_decode($payment_gateway->keys, true);
        $key = '';

        if ($payment_keys != '') {
            if ($payment_gateway->status == 1) {
                if ($payment_gateway->test_mode == 1) {
                    $key = $payment_keys['store_password'];
                } else {
                    $key = $payment_keys['store_live_password'];
                }
                if ($key == '') {
                    $msg = get_phrase('This payment gateway is not configured.');
                }
            } else {
                $msg = get_phrase('Admin denied transaction through this gateway.');
            }
        } else {
            $msg = get_phrase('This payment gateway is not configured.');
        }
    }

    // user information
    $user = DB::table('users')
        ->where('id', auth()->user()->id)
        ->first();
@endphp

<form action="{{ route('payment.create', $payment_gateway->identifier) }}" method="GET">
    @csrf
    <input type="hidden" id="payable_amount" name="payable_amount" value="{{ $payment_details['payable_amount'] }}">
    <input type="hidden" id="user_id" name="user_id" value="{{ $user->id }}">
    <input type="hidden" name="currency" value="{{ $payment_gateway->currency }}">
    <input type="hidden" id="payment_type" name="payment_type" value="{{ $payment_gateway->title }}">
    <input type="hidden" id="items_id" name="items_id" value="{{ $payment_details['items'][0]['id'] }}">
    <input type="hidden" id="sslcz_storeid" name="sslcz_storeid" value="{{ $key }}">

    <button class="btn btn-primary" type="submit">Pay by SSLcommerz</button>
</form>
