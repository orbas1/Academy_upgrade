<div class="row">
    <div class="col-md-12">
        @if ($booking_list->count() > 0)
            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                <p class="admin-tInfo">
                    {{ get_phrase('Showing') . ' ' . count($booking_list) . ' ' . get_phrase('of') . ' ' . $booking_list->total() . ' ' . get_phrase('data') }}
                </p>
            </div>
            <div class="table-responsive package_list overflow-auto" id="package_list">
                <table class="eTable eTable-2 print-table table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" class="">{{ get_phrase('Student Name') }}</th>
                            <th scope="col" class="text-center">{{ get_phrase('Subject') }}</th>
                            <th scope="col" class="text-center">{{ get_phrase('Paid Amount') }}</th>
                            <th scope="col" class="text-center">{{ get_phrase('Time') }}</th>
                            <th scope="col" class="print-d-none text-center">{{ get_phrase('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($booking_list as $key => $booking)
                            <tr>
                                <th scope="row">
                                    <p class="row-number">{{ ++$key }}</p>
                                </th>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-1 mt-1">
                                            <h4 class="title fs-14px">
                                                <a href="">
                                                    {{ $booking->booking_to_student->name }}
                                                </a>
                                            </h4>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="sub-title2 text-12px text-center">
                                        <p>
                                            <strong>
                                                {{ $booking->booking_to_schedule->schedule_to_tutorSubjects->name }}
                                            </strong>
                                        </p>

                                    </div>
                                </td>
                                <td>
                                    <div class="sub-title2 text-12px text-center">
                                        <p>
                                            {{ $booking->booking_to_schedule->schedule_to_tutorCanteach->price }}
                                        </p>
                                    </div>
                                </td>
                                <td>
                                    <div class="sub-title2 text-12px text-center">
                                        <p>
                                            {{ date('d M, Y', $booking->start_time) }}
                                        </p>
                                        <p>
                                            {{ date('h:i a', $booking->start_time) . ' - ' . date('h:i a', $booking->end_time) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('instructor.tution_class.join', $booking->id) }}"
                                        data-bs-toggle="tooltip" title="{{ get_phrase('Join Class') }}" class="edit-delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" id="live-class-icon"
                                            data-name="Layer 1" viewBox="0 0 24 24" width="18"
                                            height="18">
                                            <path
                                                d="m14.417,16.902c.492.559.691,1.327.578,2.224l-.541,2.865c-.257,1.172-1.454,2.009-2.454,2.009s-2.172-.837-2.423-1.98l-.536-2.834c-.124-.956.076-1.725.568-2.283.528-.599,1.333-.902,2.392-.902,1.083,0,1.888.304,2.416.902Zm-.417-4.902c0-1.105-.895-2-2-2s-2,.895-2,2,.895,2,2,2,2-.895,2-2ZM12,0C5.383,0,0,5.383,0,12c0,4.071,2.039,7.831,5.453,10.059.463.301,1.083.171,1.384-.292.302-.462.171-1.082-.291-1.384-2.847-1.856-4.546-4.99-4.546-8.383C2,6.486,6.486,2,12,2s10,4.486,10,10c0,3.393-1.699,6.526-4.546,8.383-.462.302-.593.922-.291,1.384.191.294.512.454.838.454.188,0,.376-.053.545-.162,3.415-2.228,5.453-5.987,5.453-10.059C24,5.383,18.617,0,12,0Zm5.833,15.871c.764-1.148,1.167-2.487,1.167-3.871,0-3.859-3.14-7-7-7s-7,3.141-7,7c0,1.336.402,2.657,1.163,3.822.302.463.921.593,1.384.29.462-.302.592-.921.291-1.384-.548-.839-.837-1.782-.837-2.729,0-2.757,2.243-5,5-5s5,2.243,5,5c0,.988-.288,1.944-.833,2.764-.306.46-.181,1.081.279,1.387.17.113.363.167.553.167.324,0,.641-.156.833-.446Z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                <p class="admin-tInfo">
                    {{ get_phrase('Showing') . ' ' . count($booking_list) . ' ' . get_phrase('of') . ' ' . $booking_list->total() . ' ' . get_phrase('data') }}
                </p>
                {{ $booking_list->links() }}
            </div>
        @else
            @include('instructor.no_data')
        @endif
    </div>
</div>