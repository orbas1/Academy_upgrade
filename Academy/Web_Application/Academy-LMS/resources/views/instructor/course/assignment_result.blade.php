<style>
    .font-color {
        color: #4B5675;
        font-weight: 500;
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
                <h6 class="title">{{ get_phrase('Submitted assignment result') }}:</h6>
            </div>
            <div class="col-md-6">
                <a class="btn ol-btn-outline-secondary float-end"
                    onclick="loadView('{{ route('view', ['path' => 'instructor.course.assignment_submission', 'id' => $assignment->id, 'course_id' => $course_id]) }}','#view_submission')"><i
                        class="fi-rr-arrow-left"></i>
                    {{ get_phrase('Back to assignment list') }}</a>
            </div>
        </div>
    </div>
    <hr>
    <div class="col-md-12">
        <div>
            <p class="font-color mb-2">{{ get_phrase('Student name') }}: <span
                    class="title">{{ $submitted_assignment->user->name }}</span></p>
            <p class="font-color mb-2">{{ get_phrase('Marks') }}: <span
                    class="title">{{ $submitted_assignment->marks }}</span>
            </p>
            <p class="font-color mb-2">{{ get_phrase('Remarks') }}: <span
                    class="title">{{ $submitted_assignment->remarks }}</span></p>
        </div>
    </div>

</div>
