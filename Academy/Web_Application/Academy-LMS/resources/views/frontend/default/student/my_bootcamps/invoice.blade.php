@extends('layouts.default')
@push('title', get_phrase('Invoice'))
@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/backend/css/style.css') }}">
<style>
    .table-responsive {
        min-height: 250px;
    }
</style>
@endpush
@section('content')
<section class="my-course-content mt-50">
    <div class="profile-banner-area"></div>
    <div class="container profile-banner-area-container">
        <div class="row">
            @include('frontend.default.student.left_sidebar')

            <div class="col-lg-9">
                <h4 class="g-title text-capitalize">{{ get_phrase('Invoice') }}</h4>
                <div class="my-panel mt-5">
                    <div class="ol-card mb-30px">
                        <div class="col-sm-2 float-end col-md-2 p-0">
                            <a href="{{ url()->previous() }}"
                                class="eBtn gradient float-md-end"><i class="fas fa-arrow-left"></i> {{ get_phrase('Back') }}
                            </a>
                        </div>
                        <div class="ol-card-body p-20px" id="my-invoice">
                            <div class="pb-20px ol-border-bottom mb-30px">
                                <div class="mb-20px">
                                    <h5 class="title fs-16px mb-10px text-capitalize">{{ get_phrase('Invoice') }}</h5>
                                    <p class="sub-title fs-16px text-break">{{ $invoice->invoice }}</p>
                                </div>
                                <ul class="ol-list-group-2 max-w-280px">
                                    <li>
                                        <span class="title fs-16px fw-normal text-capitalize">{{ get_phrase('Issue Date') }}</span>
                                        <span class="title2 fs-16px">{{ date('d M, Y') }}</span>
                                    </li>
                                    <li>
                                        <span class="title fs-16px fw-normal text-capitalize">{{ get_phrase('Purchase Date') }}</span>
                                        <span class="title2 fs-16px">{{ date('d M, Y', strtotime($invoice->created_at)) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="pb-20px ol-border-bottom mb-20px">
                                <div class="row">
                                    <div class="col-lg-10">
                                        <div class="d-flex gap-3 justify-content-between flex-wrap">
                                            <div>
                                                <h4 class="title fs-18px text-capitalize mb-20px">{{ get_phrase('Invoice To') }}</h4>
                                                @php
                                                    $user = get_user_info($invoice->user_id);
                                                @endphp
                                                <ul class="ol-list-group-2">
                                                    <li class="title fs-16px fw-normal text-capitalize">{{ $user->name }}</li>
                                                    <li class="title fs-16px fw-normal text-capitalize">{{ $user->email }}</li>
                                                    <li class="title fs-16px fw-normal text-capitalize">{{ $user->address }}</li>
                                                    <li class="title fs-16px fw-normal text-capitalize">{{ $user->phone }}</li>
                                                </ul>
                                            </div>
                                            <div class="max-w-280px w-100">
                                                <h4 class="title fs-18px text-capitalize mb-20px">{{ get_phrase('Payment Details') }}</h4>
                                                <ul class="ol-list-group-2 w-100">
                                                    <li>
                                                        <span
                                                            class="title fs-16px fw-normal text-capitalize">{{ get_phrase('Total') }}</span>
                                                        <span
                                                            class="title2 fs-16px">{{ currency($invoice->price, 2) }}</span>
                                                    </li>
                                                    <li>
                                                        <span
                                                            class="title fs-16px fw-normal text-capitalize">{{ get_phrase('Due') }}</span>
                                                        <span
                                                            class="title2 fs-16px">{{ currency($invoice->price, 2) }}</span>
                                                    </li>
                                                    <li>
                                                        <span
                                                            class="title fs-16px fw-normal text-capitalize">{{ get_phrase('Payment Method') }}</span>
                                                        <span class="title2 fs-16px text-capitalize">{{ $invoice->payment_method }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="invoice-table-wrap">
                                <!-- Table  -->
                                <div class="table-responsive">
                                    <table class="table ol-table mb-3 text-nowrap">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ get_phrase('Description') }}</th>
                                                <th scope="col" class="text-center">{{ get_phrase('Quantity') }}</th>
                                                <th scope="col" class="text-center">{{ get_phrase('Price') }}</th>
                                                <th scope="col" class="text-end">{{ get_phrase('Amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{ $invoice->title }}</td>
                                                <td class="text-center">1</td>
                                                <td class="text-center">{{ currency($invoice->price, 2) }}</td>
                                                <td class="text-end">{{ currency($invoice->price, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                            </div>
                        </div>
                        <a href="#" onclick="printableDiv('my-invoice');"
                                    class="btn ol-btn-light-primary ol-btn-rounded print-d-none">{{ get_phrase('Print') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function printableDiv(printableAreaDivId) {
        var printContents = document.getElementById(printableAreaDivId).innerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;

        window.print();

        document.body.innerHTML = originalContents;
    }
</script>
@endpush
