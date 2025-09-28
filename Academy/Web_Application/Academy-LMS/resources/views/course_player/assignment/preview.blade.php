<style>
    .font-color {
        color: #212534 !important;
    }

    .download-button {
        color: #6D718C;
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

<div>
    <h6 class="title mb-3" style="font-weight: 600">{{ get_phrase('Answers') }}:</h6>
    <div>
        <p class="font-color">{!! nl2br($submitted_assignment->answer) !!}</p>
    </div>
    <hr>
    <div>
        @if ($submitted_assignment->file)
            <a href="{{ asset($submitted_assignment->file) }}" class="btn download-button" download>
                <span><i class="fi fi-rr-download"></i></span><span
                    style="margin-left: 5px;">{{ get_phrase('Download answer file') }}</span>
            </a>
        @else
            <p style="color: #9195AF;font-weight: 500">{{ get_phrase('No file to download') }}</p>
        @endif
    </div>
</div>
