@extends('layouts.default')
@push('title', get_phrase('Booking Invoice ') . $invoice)
@section('content')
    <section class="my-course-content mt-50">
        <div class="profile-banner-area"></div>
        <div class="profile-banner-area-container container">
            <div class="row">
                @include('frontend.default.student.left_sidebar')


                <div class="col-lg-9">
                    <div class="my-panel purchase-history-panel">
                        <div class="invoice mt-5" id="invoice">
                            <div class="top d-flex justify-content-between align-items-center border-1 border-bottom mb-5 pb-5">
                                <div>
                                    <h2><span>{{ get_phrase('Invoice') }} {{ $invoice }}</span></h2>
                                    <p class="description">{{ get_phrase('Date ') }} {{ date('d-M-Y', $booking->start_time) }}</p>
                                </div>
                                <div>
                                    <img src="{{ asset('assets/upload/dark_logo/dark-logo-1716985438.png') }}" alt="system logo" class="object-fit-cover rounded" width="200px">
                                </div>
                            </div>

                            <div class="billing-area">
                                <div class="table-responsive">
                                    <table class="eTable table">
                                        <thead>
                                            <tr>
                                                <th>{{ get_phrase('Subject') }}</th>
                                                <th>{{ get_phrase('Tutor') }}</th>
                                                <th>{{ get_phrase('Time') }}</th>
                                                <th>{{ get_phrase('Method') }}</th>
                                                <th>{{ get_phrase('Price') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{ $booking->booking_to_schedule->schedule_to_tutorSubjects->name }}</td>
                                                <td>{{ $booking->booking_to_tutor->name }}</td>
                                                <td>
                                                    {{ date('d-M-Y', $booking->start_time) }}
                                                    {{ date('h:i a', $booking->start_time) . ' - ' . date('h:i a', $booking->end_time) }} 
                                                </td>
                                                <td class="text-capitalize">{{ get_phrase('Stripe') }}</td>
                                                <td>{{ currency($booking->booking_to_schedule->schedule_to_tutorCanteach->price, 2) }}</td>
                                            </tr>

                                            <tr>
                                                <td>{{ get_phrase('Billed to :') }}</td>
                                                <td>{{ $booking->booking_to_student->name }}</td>
                                                <td></td>
                                                <td>{{ get_phrase('Total : ') }}</td>
                                                <td>{{ currency($booking->booking_to_schedule->schedule_to_tutorCanteach->price, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end mt-3 gap-3">
                        <a class="eBtn gradient" href="{{ route('my_bookings') }}">{{ get_phrase('Back') }}</a>
                        <a class="eBtn gradient" id="print" href="javascript:void(0);" onclick="printableDiv('invoice')">{{ get_phrase('Print') }}</a>
                    </div>
                </div>
            </div>

    </section>
@endsection

@push('js')
    <script>
        "use strict";

        function printableDiv(printableAreaDivId) {
            var printContents = document.getElementById(printableAreaDivId).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
