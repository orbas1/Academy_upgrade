<div class="row g-3">
    @if ($schedules->count() != 0)
        @foreach($schedules as $schedule)
        <div class="col-xl-4 col-lg-6 col-md-4 col-sm-6">
            <div class="tutor-single-service">
                <h5 class="in-title-16px mb-3 fw-semibold">{{ $schedule->schedule_to_tutorSubjects->name }}</h5>
                <ul class="mb-3 d-flex flex-column gap-12px">
                    <li class="d-flex service-activity-list-item gap-6px">
                        <img src="{{ asset('assets/frontend/default/image/timer-start-gray-20.svg') }}" alt="">
                        <span class="in-title-14px">{{ date('h:i a', $schedule->start_time) . ' - ' . date('h:i a', $schedule->end_time) }}</span>
                    </li>
                    <li class="d-flex service-activity-list-item gap-6px">
                        <img src="{{ asset('assets/frontend/default/image/shopping-cart-gray-20.svg') }}" alt="">
                        <span class="in-title-14px">{{ currency($schedule->schedule_to_tutorCanTeach->price).'/'.get_phrase('session') }}</span>
                    </li>
                    <li class="d-flex service-activity-list-item gap-6px">
                        <img src="{{ asset('assets/frontend/default/image/user-gray-20.svg') }}" alt="">
                        <span class="in-title-14px">{{ total_booked_schedule_by_schedule_id($schedule->id).' '.get_phrase('slot booked') }}</span>
                    </li>
                </ul>
                <a onclick="tutorServiceModal('{{ route('view', ['path' => 'frontend.default.tutor_booking.details_modal', 'schedule_id' => $schedule->id]) }}')" href="#" class="btn lms-sm-btn-outline-secondary w-100">
                    {{ get_phrase('View Details') }}
                </a>
            </div>
        </div>
        @endforeach
    @else
        @include('frontend.default.empty')
    @endif
</div>
