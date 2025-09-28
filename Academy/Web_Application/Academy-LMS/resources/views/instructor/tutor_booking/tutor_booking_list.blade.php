@extends('layouts.instructor')

@push('title', get_phrase('Categories'))

@push('meta')
@endpush

@push('css')
@endpush



@section('content')

    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-18px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('List of bookings') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="ol-card">
            <div class="ol-card-body p-20px mb-3">
                <div class="d-flex gap-3 flex-wrap flex-md-nowrap">
                    <div class="ol-sidebar-tab">
                        <div class="d-flex flex-column">

                            @php
                                $tab = request('tab');
                            @endphp

                            <a class="nav-link @if ($tab == 'live_and_upcoming') active @endif"
                                href="{{ route('instructor.tutor_booking_list', ['tab' => 'live_and_upcoming']) }}">
                                <span class="fi-rr-edit"></span>
                                <span>{{ get_phrase('Live & Upcoming') }}</span>
                            </a>

                            <a class="nav-link @if ($tab == 'archive') active @endif"
                                href="{{ route('instructor.tutor_booking_list', ['tab' => 'archive']) }}">
                                <span class="icon fi-rr-duplicate"></span>
                                <span>{{ get_phrase('Archive') }}</span>
                            </a>

                        </div>
                    </div>
                    <div class="tab-content w-100">
                        @includeWhen($tab == 'live_and_upcoming', 'instructor.tutor_booking.live_and_upcoming')
                        @includeWhen($tab == 'archive', 'instructor.tutor_booking.archive')
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection