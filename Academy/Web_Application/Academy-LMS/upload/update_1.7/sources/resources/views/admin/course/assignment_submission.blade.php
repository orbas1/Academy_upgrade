<style>
    .review-button {
        color: black;
        font-weight: 500;
        border: 1px solid #E0E5F3;
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 8px;
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    .review-button:hover {
        color: #007bff;
        border-color: #007bff;
    }

    .badge.bg-primary {
        background-color: #1b84ff !important;
        border-radius: 5px;
        color: white !important;
        border: 1px solid #1b84ff !important;
        font-weight: 600;
        font-size: 10px;
    }

    .font-color {
        color: #4B5675;
        font-weight: 500;
    }
</style>

@php
    $assignment = App\Models\Assignment::where('id', $id)->first();
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="row align-items-center">
            <div class="col-md-6 ">
                <h6><span class="title">{{ get_phrase('Assignment name') }}</span> : <span
                        class="font-color">{{ $assignment->title }}</span></h6>
            </div>

            <div class="col-md-6 pb-3">
                <a class="btn ol-btn-outline-secondary float-end"
                    onclick="loadView('{{ route('view', ['path' => 'admin.course.assignment', 'id' => $assignment->id, 'course_id' => $course_id]) }}','#view_submission')"><i
                        class="fi-rr-arrow-left"></i>
                    {{ get_phrase('Back to assignment list') }}</a>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="table-responsive overflow-auto">
            <table class="table eTable eTable-2 print-table">
                <thead>
                    <th scope="col">#</th>
                    <th scope="col">{{ get_phrase('Student') }}</th>
                    <th scope="col">{{ get_phrase('Marks') }}</th>
                    <th scope="col" class="print-d-none">{{ get_phrase('Status') }}</th>
                    <th scope="col" class="print-d-none">{{ get_phrase('Options') }}</th>
                </thead>
                <tbody>

                    @foreach (App\Models\SubmittedAssignment::where('assignment_id', $id)->get() as $key => $submitted_assignment)
                        <tr>
                            <th scope="row">
                                <p class="row-number">{{ ++$key }}</p>
                            </th>
                            <td>
                                <div class="dAdmin_profile d-flex align-items-center min-w-200px">
                                    <div class="dAdmin_profile_name">
                                        <h4 class="title fs-14px">
                                            {{ $submitted_assignment->user->name }}
                                        </h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="sub-title2 text-12px">
                                    @if (isset($submitted_assignment->marks))
                                        <span>{{ $submitted_assignment->marks }}</span>
                                    @else
                                        <span class="badge bg-primary">{{ get_phrase('Pending') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p class="sub-title text-12px">
                                    @if ($submitted_assignment->status)
                                        <span class="badge bg-primary">{{ get_phrase('Reviewed') }}</span>
                                    @else
                                        <span class="badge bg-primary">{{ get_phrase('Pending') }}</span>
                                    @endif
                                </p>
                            </td>
                            <td>
                                @if ($submitted_assignment->status)
                                    <a class="btn review-button"
                                        onclick="loadView('{{ route('view', ['path' => 'admin.course.assignment_result', 'id' => $assignment->id, 'user_id' => $submitted_assignment->user_id, 'course_id' => $course_id]) }}','#view_submission')">
                                        {{ get_phrase('Review') }}</a>
                                @else
                                    <a class="btn review-button"
                                        onclick="loadView('{{ route('view', ['path' => 'admin.course.assignment_review', 'id' => $assignment->id, 'user_id' => $submitted_assignment->user_id, 'course_id' => $course_id]) }}','#view_submission')">
                                        {{ get_phrase('View Submission') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
