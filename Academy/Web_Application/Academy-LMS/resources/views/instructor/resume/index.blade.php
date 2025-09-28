@extends('layouts.instructor')
@push('title', get_phrase('Resume Manager'))
@section('content')

    @php
        $auth = auth()->user();
        $educations = json_decode($auth->educations, true);
    @endphp

    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-12px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('Manage resume') }}
                </h4>

                <a href="#" onclick="ajaxModal('{{ route('modal', ['instructor.resume.add_education']) }}', '{{ get_phrase('Add New Education') }}')" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px">
                    <span class="fi-rr-plus"></span>
                    <span>{{ get_phrase('Add New Education') }}</span>
                </a>
            </div>
        </div>
    </div>


    <!-- Start Admin area -->
    <div class="row">
        <div class="col-12">
            <div class="ol-card">
                <div class="ol-card-body p-3 mb-5">
                    

                    <div class="row">
                        <div class="col-md-12">
                            @if (isset($educations) && count($educations) > 0)
                                <div
                                    class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                                    <p class="admin-tInfo">
                                        {{ get_phrase('Showing') . ' ' . count($educations) . ' ' . get_phrase('data') }}
                                    </p>
                                </div>
                                <div class="table-responsive overflow-auto course_list overflow-auto" id="course_list">
                                    <table class="table eTable eTable-2 print-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">{{ get_phrase('Title') }}</th>
                                                <th scope="col">{{ get_phrase('City & Country') }}</th>
                                                <th scope="col">{{ get_phrase('Start Date') }}</th>
                                                <th scope="col">{{ get_phrase('End Date') }}</th>
                                                <th scope="col" class="print-d-none">{{ get_phrase('Status') }}</th>
                                                <th scope="col" class="print-d-none">{{ get_phrase('Options') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($educations as $key => $education)
                                                @php $index = $key @endphp
                                                <tr>
                                                    <th scope="row">
                                                        <p class="row-number">{{ ++$key }}</p>
                                                    </th>
                                                    <td>
                                                        <div class="dAdmin_profile d-flex align-items-center min-w-200px">
                                                            <div class="dAdmin_profile_name">
                                                                <h4 class="title fs-14px">
                                                                    {{ ucfirst($education['title']) }}
                                                                </h4>

                                                                <p class="sub-title2 text-12px">
                                                                    {{ get_phrase('Institute') }}:
                                                                    {{ get_phrase($education['institute']) }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="sub-title2 text-12px">
                                                            <p>{{ $education['city']. ', ' .$education['country'] }}</p>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="sub-title2 text-12px">
                                                            <p>{{ $education['start_date'] }}</p>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="sub-title2 text-12px">
                                                            <p>{{ $education['end_date'] ?? 'N/A' }}</p>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="dAdmin_info_name min-w-150px">
                                                            @if ($education['status'] == 'completed')
                                                                <p class="eBadge ebg-soft-success">
                                                                    {{ get_phrase('Completed') }}
                                                                </p>
                                                            @else
                                                                <p class="eBadge ebg-soft-danger">
                                                                    {{ get_phrase('Ongoing') }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="print-d-none">

                                                        <div
                                                            class="dropdown ol-icon-dropdown ol-icon-dropdown-transparent">
                                                            <button class="btn ol-btn-secondary dropdown-toggle"
                                                                type="button" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <span class="fi-rr-menu-dots-vertical"></span>
                                                            </button>

                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="#" onclick="ajaxModal('{{ route('modal', ['instructor.resume.edit_education', 'index' => $index]) }}', '{{ get_phrase('Update Education') }}')">{{ get_phrase('Edit') }}</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        onclick="confirmModal('{{ route('instructor.manage.education.remove', $index) }}')"
                                                                        href="javascript:void(0)">{{ get_phrase('Delete') }}</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                @include('instructor.no_data')
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Admin area -->
@endsection
