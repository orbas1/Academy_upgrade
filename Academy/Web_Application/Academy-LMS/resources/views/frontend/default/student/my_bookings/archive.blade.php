@if ($my_archive_bookings->count() > 0)
    <div class="table-responsive">
        <table class="table eTable">
            <thead>
                <tr>
                    <th>{{ get_phrase('Subject') }}</th>
                    <th>{{ get_phrase('Tutor') }}</th>
                    <th>{{ get_phrase('Time') }}</th>
                    <th>{{ get_phrase('Amount') }}</th>
                    <th>{{ get_phrase('Payment Method') }}</th>
                    <th>{{ get_phrase('Invoice') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($my_archive_bookings as $my_booking)
                    <tr>
                        <td>{{ $my_booking->booking_to_schedule->schedule_to_tutorSubjects->name }}</td>
                        <td>{{ $my_booking->booking_to_tutor->name }}</td>
                        <td>{{ date('h:i a', $my_booking->start_time) . ' - ' . date('h:i a', $my_booking->end_time) }}</td>
                        <td>{{ currency($my_booking->booking_to_schedule->schedule_to_tutorCanteach->price) }}</td>
                        <td>{{ get_phrase('Stripe') }}</td>
                        <td>
                            <a href="{{ route('booking_invoice', $my_booking->id) }}"
                                class="d-flex align-items-center justify-content-center btn btn-primary text-18 text-white py-3" data-bs-toggle="tooltip" data-bs-title="{{get_phrase('Print Invoice')}}">
                                <i class="fi fi-rr-print d-inline-flex"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="row bg-white radius-10 mx-2">
        <div class="com-md-12">
            @include('frontend.default.empty')
        </div>
    </div>
@endif

<!-- Pagination -->
@if (count($my_archive_bookings) > 0)
<div class="entry-pagination">
    <nav aria-label="Page navigation example">
        {{ $my_archive_bookings->appends(['tab' => 'archive'])->links() }}
    </nav>
</div>
@endif
<!-- Pagination -->