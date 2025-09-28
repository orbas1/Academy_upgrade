<?php

use App\Http\Controllers\OfflinePaymentController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::controller(PaymentController::class)->middleware('auth')->group(function () {
    Route::get('payment', 'index')->name('payment');
    Route::get('payment/show_payment_gateway_by_ajax/{identifier}', 'show_payment_gateway_by_ajax')->name('payment.show_payment_gateway_by_ajax');
    Route::any('payment/success/{identifier?}', 'payment_success')->name('payment.success');
    Route::get('payment/create/{identifier}', 'payment_create')->name('payment.create');

    // razor pay
    Route::post('payment/{identifier}/order', 'payment_razorpay')->name('razorpay.order');

    // paytm pay
    Route::get('payment/make/paytm/order', 'make_paytm_order')->name('make.paytm.order');
    Route::get('payment/make/{identifier}/status', 'paytm_paymentCallback')->name('payment.status');

    // doku pay
    Route::post('payment/doku_checkout/{identifier}', 'doku_checkout')->name('payment.doku_checkout');


});

Route::any('payment-notification/{identifier?}', [PaymentController::class, 'payment_notification'])->name('payment.notification');