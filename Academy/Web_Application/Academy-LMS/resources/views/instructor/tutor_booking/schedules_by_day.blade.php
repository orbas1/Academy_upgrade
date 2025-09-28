@extends('layouts.instructor')

@push('title', get_phrase('Manage Schedules'))

@push('meta')
@endpush

@push('css')
@endpush



@section('content')
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-12px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('Schedules By Date').' - '.$selected_date }}
                </h4>

                <a href="{{ route('instructor.manage_schedules') }}" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px ms-auto">
                    <span class="fi-rr-arrow-left"></span>
                    <span>{{ get_phrase('Back') }}</span>
                </a>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="ol-card">
                <div class="ol-card-body mb-5 p-3">
                    @if ($schedules->count() > 0)
                    <div class="row mb-4 mt-3">
                        <div class="col-md-6 col-lg-3 col-xl-6 d-flex align-items-center gap-3">
                            <div class="custom-dropdown ms-2">
                                <button class="dropdown-header btn ol-btn-light">
                                    {{ get_phrase('Export') }}
                                    <i class="fi-rr-file-export ms-2"></i>
                                </button>
                                <ul class="dropdown-list">
                                    <li>
                                        <a class="dropdown-item export-btn" href="#" onclick="downloadPDF('.print-table', 'schedule-list')"><i class="fi-rr-file-pdf"></i> {{ get_phrase('PDF') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item export-btn" href="#" onclick="window.print();"><i class="fi-rr-print"></i> {{ get_phrase('Print') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                                <p class="admin-tInfo">
                                    {{ get_phrase('Showing') . ' ' . count($schedules) . ' ' . get_phrase('of') . ' ' . $schedules->total() . ' ' . get_phrase('data') }}
                                </p>
                            </div>
                            <div class="table-responsive package_list overflow-auto" id="package_list">
                                <table class="eTable eTable-2 print-table table">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">{{ get_phrase('Subject Title') }}</th>
                                            <th scope="col">{{ get_phrase('Start time') }}</th>
                                            <th scope="col">{{ get_phrase('End time') }}</th>
                                            <th scope="col" class="print-d-none">{{ get_phrase('Status') }}</th>
                                            <th scope="col" class="print-d-none text-center">{{ get_phrase('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($schedules as $key => $schedule)
                                            @php
                                                $check = total_booked_schedule_by_schedule_id($schedule->id);
                                                if($check > 0) {
                                                    $status = 'active';
                                                    $slot_status = 'Booked';
                                                } else {
                                                    $status = 'primary';
                                                    $slot_status = 'Available';
                                                }
                                            @endphp
                                            <tr>
                                                <th scope="row">
                                                    <p class="row-number">{{ ++$key }}</p>
                                                </th>

                                                @php
                                                $categoryName = $schedule->schedule_to_tutorCategory->name;
                                                $subjectName = $schedule->schedule_to_tutorSubjects->name;
                                                @endphp
                                                <td scope="row">
                                                    <div class="dAdmin_profile d-flex align-items-center min-w-200px">
                                                        <div class="dAdmin_profile_name">
                                                            <h4 class="title fs-14px">
                                                                {{ $subjectName }}
                                                            </h4>

                                                            <p class="sub-title2 text-12px">
                                                                {{ get_phrase('Category') }}:
                                                                {{ $categoryName }}</p>
                                                        </div>
                                                    </div>
                                                </td>
    
                                                <td scope="row">
                                                    <p>{{ date('h:i A', (int)$schedule->start_time) }}</span></p>
                                                </td>
                                                <td scope="row">
                                                    <p>{{ date('h:i A', (int)$schedule->end_time) }}</span></p>
                                                </td>

                                                <td class="print-d-none">
                                                    <span class="badge bg-{{ $status }}">{{ get_phrase(ucfirst($slot_status)) }}</span>
                                                </td>

                                                <td class="print-d-none text-center">
                                                    <div class="dropdown ol-icon-dropdown ol-icon-dropdown-transparent d-flex justify-content-center">
                                                        <button class="btn ol-btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="fi-rr-menu-dots-vertical"></span>
                                                        </button>
    
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('instructor.schedule_edit', ['id' => $schedule->id]) }}">{{ get_phrase('Edit schedule') }}</a>
                                                            </li>
                                                            @if($check == 0)
                                                            <li>
                                                                <a class="dropdown-item" onclick="confirmModal('{{ route('instructor.schedule_delete', $schedule->id) }}')" href="javascript:void(0)">{{ get_phrase('Delete schedule') }}</a>
                                                            </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center gr-15 flex-wrap">
                                <p class="admin-tInfo">
                                    {{ get_phrase('Showing') . ' ' . count($schedules) . ' ' . get_phrase('of') . ' ' . $schedules->total() . ' ' . get_phrase('data') }}
                                </p>
                                {{ $schedules->links() }}
                            </div>
                        </div>
                    </div>
    
                    @else
                        @include('instructor.no_data')
                    @endif
    
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
