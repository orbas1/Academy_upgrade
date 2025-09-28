@extends('layouts.admin')
@push('title', get_phrase(' Instructor revenue'))
@push('meta')@endpush
@push('css')@endpush
@section('content')

    {{-- sammo --}}
    <!-- Start instructor area -->
    <div class="row">
        <div class="col-12">
            <div class="ol-card">
                <div class="ol-card-body p-3">
                    <div class="row mb-3 mt-3 print-d-none">
                        <div class="col-md-6  pt-2 pt-md-0">
                            @if ($purchases->count() > 0)
                                <div class="custom-dropdown">
                                    <button class="dropdown-header btn ol-btn-light">
                                        {{ get_phrase('Export') }}
                                        <i class="fi-rr-file-export ms-2"></i>
                                    </button>
                                    <ul class="dropdown-list">
                                        <li>
                                            <a class="dropdown-item" href="#"
                                                onclick="downloadPDF('.print-table', 'instructor_list')"><i
                                                    class="fi-rr-file-pdf"></i> {{ get_phrase('PDF') }}</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="window.print();"><i
                                                    class="fi-rr-print"></i> {{ get_phrase('Print') }}</a>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <form class="form-inline" action="{{ route('admin.ebook.instructor-revenue') }}" method="get">
                                <div class="row">
                                    <div class="col-9">
                                        <div class="mb-3 position-relative position-relative">
                                            <input type="text" class="form-control ol-form-control daterangepicker w-100"
                                                name="eDateRange"value="{{ date('m/d/Y', $start_date) . ' - ' . date('m/d/Y', $end_date) }}" />
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <button type="submit" class="btn ol-btn-primary w-100" id="submit-button"
                                            onclick="update_date_range();"> {{ get_phrase('Filter') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Table -->
                    @if (count($purchases) > 0)
                        <div
                            class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                            <p class="admin-tInfo">
                                {{ get_phrase('Showing') . ' ' . count($purchases) . ' ' . get_phrase('of') . ' ' . $purchases->total() . ' ' . get_phrase('data') }}
                            </p>
                            {{ $purchases->links() }}
                        </div>
                        <div class="table-responsive overflow-auto instructor_list" id="instructor_list">
                            <table class="table eTable eTable-2 print-table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ get_phrase('Enroled User') }}</th>
                                        <th scope="col">{{ get_phrase('Ebook Title') }}</th>
                                        <th scope="col">{{ get_phrase('Price') }}</th>
                                        <th scope="col">{{ get_phrase('Instructor Revenue') }}</th>
                                        <th scope="col">{{ get_phrase('Payment') }}</th>
                                        <th scope="col">{{ get_phrase('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchases as $key => $purchase)
                                        <tr>
                                            <th scope="row">
                                                <p class="row-number">{{ $key + 1 }}</p>
                                            </th>

                                            <td>
                                                <div class="dAdmin_profile d-flex align-items-center min-w-200px gap-1">
                                                    <div class="dAdmin_profile_img">
                                                        <img class="img-fluid rounded-circle" width="45" height="45"
                                                            src="{{ get_image($purchase->photo) }}" id="blog-thumbnail" />
                                                    </div>
                                                    <div class="dAdmin_profile_name">
                                                        <h4 class="title fs-14px">
                                                            {{ get_user_info($purchase->user_id)->name }}
                                                        </h4>
                                                        <p class="text-muted">
                                                            {{ get_user_info($purchase->user_id)->email }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <p>
                                                        {{ $purchase->ebook_title }}
                                                    </p>

                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <p>
                                                        {{ currency(number_format($purchase->amount, 2)) }}
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <p>
                                                        {{ currency(number_format($purchase->instructor_revenue, 2)) }}
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <p>
                                                        {{ $purchase->payment_type }}
                                                    </p>

                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <small
                                                        class="text-muted">{{ date('d M, Y', strtotime($purchase->created_at)) }}</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Data info and Pagination -->
                        <div
                            class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                            <p class="admin-tInfo">
                                {{ get_phrase('Showing') . ' ' . count($purchases) . ' ' . get_phrase('of') . ' ' . $purchases->total() . ' ' . get_phrase('data') }}
                            </p>
                            {{ $purchases->links() }}
                        </div>
                    @else
                        @include('admin.no_data')
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End instructor area -->
@endsection
@push('js')
    <script type="text/javascript">
        function update_date_range() {
            var x = $("#selectedValue").html();
            $("#date_range").val(x);
        }
    </script>
@endpush
