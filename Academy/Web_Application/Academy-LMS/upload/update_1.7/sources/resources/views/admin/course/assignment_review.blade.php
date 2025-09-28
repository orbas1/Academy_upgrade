<style>
    .font-color {
        color: #212534 !important;
        font-weight: 500 !important;
    }

    .font-color-2 {
        color: #6D718C !important;
        font-weight: 500 !important;
    }

    .provide-mark-btn {
        padding: 8px 16px !important;
        border-radius: 6px !important;
    }

    .download-button {
        color: black;
        font-weight: 500;
        border: 1px solid #E0E5F3;
        padding: 8px 16px;
        font-size: 14px;
        border-radius: 6px;
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    .download-button:hover {
        color: #007bff;
        border-color: #007bff;
    }

    #question-list ul {
        list-style: unset;
    }
</style>
@php
    if (!isset($course_details) && isset($course_id)) {
        $course_details = App\Models\Course::where('id', $course_id)->first();
    }

    $assignment = App\Models\Assignment::where('id', $id)->first();
    $student = App\Models\User::where('id', $user_id)->first();
    $submitted_assignment = App\Models\SubmittedAssignment::where('assignment_id', $id)
        ->where('user_id', $user_id)
        ->first();
@endphp

<div class="row" id="view_submission">
    <div class="col-md-12">
        <div class="row align-items-center mb-3">
            <div class="col-md-6">
                <h6 class="title">{{ get_phrase('Review assignment') }}:</h6>
            </div>
            <div class="col-md-6">
                <a class="btn ol-btn-outline-secondary float-end"
                    onclick="loadView('{{ route('view', ['path' => 'admin.course.assignment_submission', 'id' => $assignment->id, 'course_id' => $course_id]) }}','#view_submission')"><i
                        class="fi-rr-arrow-left"></i>
                    {{ get_phrase('Back to submission list') }}</a>
            </div>
        </div>
    </div>
    <hr>

    <div class="col-md-12">
        <h6 class="title mb-3">{{ get_phrase('Questions') }}:</h6>
        <div id="question-list" class="font-color ms-3 mb-4" style="font-weight: 400;">
            <p>{!! $assignment->questions !!}</p>
        </div>
        <p>
            <span class="font-color mb-4">{{ get_phrase('Note  :') }}</span><span
                style="margin-right: 3px;"></span><span>{!! nl2br($assignment->note) !!}</span>
        </p>
        <div class="mt-4 mb-3">
            @if ($assignment->question_file)
                <a href="{{ asset($assignment->question_file) }}" class="btn download-button" download>
                    <span><i class="fi fi-rr-download"></i></span><span>
                    </span>{{ get_phrase('Download Question File') }}
                </a>
            @else
                <p style="color: #9195AF;font-weight: 500">{{ get_phrase('No file to download') }}</p>
            @endif
        </div>
    </div>

    <hr>

    <div class="col-md-12 mb-3">
        <h6 class="title mb-2">{{ get_phrase('Submitted by :') }}</h6>
        <div class="dAdmin_profile d-flex align-items-center min-w-200px">
            <div class="dAdmin_profile_img">
                <img class="img-fluid rounded-circle image-45" width="45" height="45"
                    src="{{ get_image($student->photo) }}" />
            </div>
            <div class="ms-3">
                <p><span class="title fs-14px">{{ $student->name }}</span></p>
                <p><span class="sub-title2 text-12px">{{ $student->email }}</span>
                </p>
            </div>
        </div>
    </div>

    <hr>

    <div class="col-md-12">
        <h6 class="title mb-3">{{ get_phrase('Answers') }}:</h6>
        <div class="font-color mb-4" style="font-weight: 400;">
            {!! nl2br($submitted_assignment->answer) !!}
        </div>
        <p class="mb-4">
            <span class="font-color">{{ get_phrase('Note  :') }}</span><span style="margin-right: 3px;"></span><span>
                {!! nl2br($submitted_assignment->note) !!}</span>
        </p>
        <div>
            @if ($submitted_assignment->file)
                <a href="{{ asset($submitted_assignment->file) }}" class="btn download-button" download>
                    <span><i class="fi fi-rr-download"></i></span><span>
                    </span>{{ get_phrase(' Download Answer File') }}
                </a>
            @else
                <p style="color: #9195AF;font-weight: 500">{{ get_phrase('No file to download') }}</p>
            @endif
        </div>
    </div>
    <hr class="mt-3">
    <div class="col-md-12 mt-3">
        <a class="btn btn-primary provide-mark-btn btn-sm"
            onclick="ajaxModal('{{ route('modal', ['view_path' => 'admin.course.assignment_mark', 'assignment_id' => $assignment->id, 'submitted_assignment' => $submitted_assignment->id, 'course_id' => $course_id]) }}', '{{ get_phrase('Provide your assessment:') }}')"><i
                class="fi-rr-plus"></i> {{ get_phrase('Provide marks') }}</a>
    </div>

</div>
