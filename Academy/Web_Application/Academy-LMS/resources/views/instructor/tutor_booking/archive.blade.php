<div class="row">
    <div class="col-md-12">
        @if ($archive_list->count() > 0)
            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                <p class="admin-tInfo">
                    {{ get_phrase('Showing') . ' ' . count($archive_list) . ' ' . get_phrase('of') . ' ' . $archive_list->total() . ' ' . get_phrase('data') }}
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($archive_list as $key => $booking)
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                <p class="admin-tInfo">
                    {{ get_phrase('Showing') . ' ' . count($archive_list) . ' ' . get_phrase('of') . ' ' . $archive_list->total() . ' ' . get_phrase('data') }}
                </p>
                {{ $archive_list->links() }}
            </div>
        @else
            @include('instructor.no_data')
        @endif
    </div>
</div>