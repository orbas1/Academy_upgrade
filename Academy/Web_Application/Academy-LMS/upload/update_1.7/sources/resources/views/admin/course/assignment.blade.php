@php
    if (!isset($course_details) && isset($course_id)) {
        $course_details = App\Models\Course::where('id', $course_id)->first();
    }
@endphp

<div class="row" id="view_submission">
    <div class="col-md-12 pb-3">
        <a class="btn ol-btn-primary float-end"
            onclick="ajaxModal('{{ route('modal', ['view_path' => 'admin.course.create_assignment', 'course_id' => $course_details->id]) }}', '{{ get_phrase('Add a new assignment') }}')"><i
                class="fi-rr-plus"></i> {{ get_phrase('New Assignment') }}</a>
    </div>
    <div class="col-md-12">
        <div class="table-responsive overflow-auto">
            <table class="table eTable eTable-2 print-table">
                <thead>
                    <th scope="col">#</th>
                    <th scope="col">{{ get_phrase('Assignment Name') }}</th>
                    <th scope="col">{{ get_phrase('Deadline') }}</th>
                    <th scope="col">{{ get_phrase('Number of submission') }}</th>
                    <th scope="col" class="print-d-none">{{ get_phrase('Status') }}</th>
                    <th scope="col" class="print-d-none">{{ get_phrase('Options') }}</th>
                </thead>
                <tbody>
                    @foreach (App\Models\Assignment::where('course_id', $course_details->id)->get() as $key => $assignment)
                        <tr>
                            <th scope="row">
                                <p class="row-number">{{ ++$key }}</p>
                            </th>
                            <td>
                                <div class="dAdmin_profile d-flex align-items-center min-w-200px">
                                    <div class="dAdmin_profile_name">
                                        <h4 class="title fs-14px">
                                            {{ $assignment->title }}
                                        </h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="sub-title2 text-12px">
                                    {{ $assignment->deadline }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $totalSubmission = App\Models\SubmittedAssignment::where(
                                        'assignment_id',
                                        $assignment->id,
                                    )->count();
                                @endphp
                                <div class="sub-title2 text-12px">
                                    <span>{{ $totalSubmission }} <span>{{ get_phrase('Students') }}</span></span>
                                </div>
                            </td>
                            <td class="print-d-none">
                                @if ($assignment->status == 'active' && strtotime($assignment->deadline) >= time())
                                    <span
                                        class="badge bg-{{ $assignment->status }}">{{ get_phrase(ucfirst($assignment->status)) }}</span>
                                @elseif ($assignment->status == 'draft')
                                    <span
                                        class="badge bg-{{ $assignment->status }}">{{ get_phrase(ucfirst($assignment->status)) }}</span>
                                @elseif (strtotime($assignment->deadline) < time())
                                    <span class="badge bg-danger">{{ get_phrase('Expired') }}</span>
                                @endif
                            </td>
                            <td class="print-d-none">
                                <div class="dropdown ol-icon-dropdown ol-icon-dropdown-transparent">
                                    <button class="btn ol-btn-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="fi-rr-menu-dots-vertical"></span>
                                    </button>
                                    <ul class="dropdown-menu">

                                        <li>
                                            <a class="dropdown-item"
                                                onclick="loadView('{{ route('view', ['path' => 'admin.course.assignment_submission', 'id' => $assignment->id, 'course_id' => $course_details->id]) }}','#view_submission')"
                                                href="#">{{ get_phrase('View Submission') }}</a>
                                        </li>

                                        @if ($assignment->status == 'active')
                                            <li>
                                                <a class="dropdown-item"
                                                    onclick="confirmModal('{{ route('admin.assignment.status', ['type' => 'draft', 'id' => $assignment->id]) }}')"
                                                    href="#">{{ get_phrase('Make As Draft') }}</a>
                                            </li>
                                        @else
                                            <li>
                                                <a class="dropdown-item"
                                                    onclick="confirmModal('{{ route('admin.assignment.status', ['type' => 'active', 'id' => $assignment->id]) }}')"
                                                    href="#">{{ get_phrase('Make As Active') }}</a>
                                            </li>
                                        @endif

                                        <li>
                                            <a class="dropdown-item"
                                                onclick="ajaxModal('{{ route('modal', ['view_path' => 'admin.course.edit_assignment', 'id' => $assignment->id]) }}', '{{ get_phrase('Edit Assignment') }}')"
                                                href="javascript:void(0)">{{ get_phrase('Edit') }}</a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item"
                                                onclick="confirmModal('{{ route('admin.assignment.delete', ['id' => $assignment->id]) }}')"
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
    </div>
</div>
