@extends('layouts.default')
@push('title', get_phrase('Booked schedules'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <section class="wishlist-content">
        <div class="profile-banner-area"></div>
        <div class="container profile-banner-area-container">
            <div class="row">
                @include('frontend.default.student.left_sidebar')

                <div class="col-lg-9">
                    <h4 class="g-title mb-5">{{ get_phrase('Booked schedules') }}</h4>
                    <div class="my-panel purchase-history-panel">

                        <ul class="nav nav-pills mb-3 gap-4" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="{{ route('my_bookings', ['tab' => 'live-upcoming']) }}" class="nav-link gradient-border-btn {{ request('tab') === 'live-upcoming' ? 'active' : '' }}">
                                    Live & Upcoming
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="{{ route('my_bookings', ['tab' => 'archive']) }}" class="nav-link gradient-border-btn {{ request('tab') === 'archive' ? 'active' : '' }}">
                                    Archive
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade {{ request('tab') === 'live-upcoming' ? 'show active' : '' }}" id="pills-live" role="tabpanel" aria-labelledby="pills-live-tab">
                                @include('frontend.default.student.my_bookings.live_and_upcoming')
                            </div>
                            <div class="tab-pane fade {{ request('tab') === 'archive' ? 'show active' : '' }}" id="pills-archive" role="tabpanel" aria-labelledby="pills-archive-tab">
                                @include('frontend.default.student.my_bookings.archive')
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!------------ purchase history area End  ------------>
@endsection
@push('js')@endpush
