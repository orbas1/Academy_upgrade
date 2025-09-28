@php
    $model = $payment_details['success_method']['model_name'];

    if ($model == 'InstructorPayment') {
        $settings = DB::table('users')
            ->where('id', $payment_details['items'][0]['id'])
            ->value('paymentkeys');

        $keys = isset($settings) ? json_decode($settings) : null;

        $merchant_id = $merchant_password = '';
        if ($payment_gateway->test_mode == 1) {
            if ($keys) {
                $key_type = 'merchant_id';
                $key = $keys->maxicash->merchant_id;
            }
        } else {
            if ($keys) {
                $key_type = 'merchant_password';
                $key = $keys->maxicash->merchant_password;
            }
        }

        if ($key == '') {
            $msg = "This payment gateway isn't configured.";
        }
    } else {
        $payment_keys = json_decode($payment_gateway->keys, true);
        $key = $key_type = '';

        if ($payment_keys != '') {
            if ($payment_gateway->status == 1) {
                if ($payment_gateway->test_mode == 1) {
                    $key_type = 'merchant_id';
                    $key = $payment_keys['merchant_id'];
                } else {
                    $key_type = 'merchant_password';
                    $key = $payment_keys['merchant_password'];
                }
                if ($key == '') {
                    $msg = get_phrase("This payment gateway isn't configured.");
                }
            } else {
                $msg = get_phrase('Admin denied transaction through this gateway.');
            }
        } else {
            $msg = get_phrase("This payment gateway isn't configured.");
        }
    }

    $title = isset($payment_details['custom_field']['title']) ? $payment_details['custom_field']['title'] : '';
    $description = isset($payment_details['custom_field']['description'])
        ? $payment_details['custom_field']['description']
        : '';


    $user = DB::table('users')
        ->where('id', auth()->user()->id)
        ->first();
@endphp


@if ($key != '')
    <a href="{{ route('payment.create', $payment_gateway->identifier) }}"
        class="btn btn-primary py-2 px-3">{{ get_phrase('Pay by Maxicash') }}</a>
@else
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none;">
        <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
            <path
                d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
        </symbol>
    </svg>

    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:">
            <use xlink:href="#exclamation-triangle-fill" />
        </svg>
        <div class="payment_err_msg">
            <b>{{ get_phrase('Opps!') }}</b> {{ $msg }}<br>
            {{ get_phrase('Try another gateway.') }}
        </div>
    </div>
@endif
