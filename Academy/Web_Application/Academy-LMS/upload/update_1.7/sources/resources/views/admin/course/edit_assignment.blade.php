@php
    $assignment = App\Models\Assignment::where('id', $id)->first();
@endphp
<form class="required-form" action="{{ route('admin.assignment.update', ['id' => $id]) }}" method="post"
    enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="id" value="{{ $assignment->id }}">

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="title">{{ get_phrase('Assignment Title') }}<span
                class="required">*</span></label>
        <input type="text" value="{{ $assignment->title }}" name="title" class="form-control ol-form-control"
            id="title" required>
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="questions">{{ get_phrase('Questions') }}<span
                class="required">*</span></label>
        <textarea name="questions" rows="5" class="form-control ol-form-control text_editor" id="questions">{{ $assignment->questions }}</textarea>
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="question_file">{{ get_phrase('Question File') }}</label>
        <input type="file" value="{{ $assignment->question_file }}" name="question_file"
            class="form-control ol-form-control" id="question_file" />
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="total_marks">{{ get_phrase('Total Marks') }}<span
                class="required">*</span></label>
        <input type="number" value="{{ $assignment->total_marks }}" class="form-control ol-form-control"
            name="total_marks" id="total_marks">
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="">{{ get_phrase('Deadline') }}<span
                class="required">*</span></label>
        <input type="datetime-local" value="{{ $assignment->deadline }}" class="form-control ol-form-control"
            value="" data-toggle="date-picker" data-single-date-picker="true" name="deadline" id="deadline">
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="">{{ get_phrase('Note') }}</label>
        <textarea class="form-control ol-form-control" name="note" id="note" rows="4">{{ $assignment->note }}</textarea>
    </div>

    <div class="fpb-7 mb-3">
        <button type="submit" class="ol-btn-primary">{{ get_phrase('Update') }}</button>
    </div>

</form>

@include('admin.init')
