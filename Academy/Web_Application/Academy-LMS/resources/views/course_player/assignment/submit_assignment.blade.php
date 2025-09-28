@php
    $assignment = App\Models\Assignment::where('id', $id)->first();
@endphp

<div class="row" id="pills-assignment">

    <div class="col-md-12">

        <div class="row mb-3">

            <div class="col-md-6">
                <h6>{{ get_phrase('Submit assignment form') }}:</h6>
            </div>

            <div class="col-md-6">
                <a class="btn btn-outline-secondary float-end"
                    onclick="loadView('{{ route('view', ['path' => 'course_player.assignment.index', 'id' => $assignment->id, 'tab' => 'assignment', 'course_id' => $course_id]) }}','#pills-assignment')"><i
                        class="fi-rr-arrow-left"></i>
                    {{ get_phrase('Back to assignment list') }}</a>
            </div>

        </div>

        <div class="mb-3">
            <h6 class="mb-2">{{ get_phrase('Questions') }}:</h6>
            <p>
                {!! $assignment->questions !!}
            </p>
             @if($assignment->question_file && file_exists(public_path($assignment->question_file)))
                <a href="{{ asset($assignment->question_file) }}" 
                class="btn btn-sm custom-btn eBtn gradient" 
                download>
                {{ get_phrase('Download question file') }}
                </a>
            @endif
        </div>
           
        <div>
            <form class="required-form" action="{{ route('student.assignment.submit') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">

                <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                <div class="fpb-7 mb-3">
                    <label class="form-label ol-form-label" for="">{{ get_phrase('Answer') }}</label>
                    <textarea class="form-control ol-form-control" name="answer" id="answer" rows="4"></textarea>
                </div>

                <div class="fpb-7 mb-3">
                    <label class="form-label ol-form-label"
                        for="file">{{ get_phrase('Upload File (optional)') }}</label>
                    <input type="file" name="file" class="form-control ol-form-control" id="file" />
                </div>

                <div class="fpb-7 mb-3">
                    <label class="form-label ol-form-label"
                        for="">{{ get_phrase('Enter your private note (optional)') }}</label>
                    <textarea class="form-control ol-form-control" name="note" id="note" rows="4"></textarea>
                </div>

                <div class="fpb-7 mb-3">
                    <button type="submit" class="btn btn-primary">{{ get_phrase('Submit') }}</button>
                </div>

            </form>
            @include('admin.init')
            {{-- @include('course_player.init') --}}
        </div>

    </div>

</div>
