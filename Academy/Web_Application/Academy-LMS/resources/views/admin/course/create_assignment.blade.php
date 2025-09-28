<form class="required-form" action="{{ route('admin.assignment.store', ['course_id' => $course_id]) }}" method="post"
    enctype="multipart/form-data">
    @csrf

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="title">{{ get_phrase('Assignment Title') }}<span
                class="required">*</span></label>
        <input type="text" name="title" class="form-control ol-form-control" id="title" required>
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="questions">{{ get_phrase('Questions') }}<span
                class="required">*</span></label>
        <textarea name="questions" rows="5" class="form-control ol-form-control text_editor" id="questions"></textarea>
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="question_file">{{ get_phrase('Question File(Optional)') }}</label>
        <input type="file" name="question_file" class="form-control ol-form-control" id="question_file" />
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="total_marks">{{ get_phrase('Total Marks') }}<span
                class="required">*</span></label>
        <input type="number" class="form-control ol-form-control" name="total_marks" id="total_marks">
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="">{{ get_phrase('Deadline') }}<span
                class="required">*</span></label>
        <input type="datetime-local" class="form-control ol-form-control" value="" data-toggle="date-picker"
            data-single-date-picker="true" name="deadline" id="deadline">
    </div>

    <div class="fpb-7 mb-3">
        <label class="form-label ol-form-label" for="">{{ get_phrase('Note') }}</label>
        <textarea class="form-control ol-form-control" name="note" id="note" rows="4"></textarea>
    </div>

    <div class="row fpb-7 mb-3 ">
        <label class="form-label ol-form-label" for="">{{ get_phrase('Submission status') }}<span
                class="required">*</span></label>
        <div class="col-sm-10">
            <div class="eRadios">
                <div class="form-check">
                    <input type="radio" value="active" name="status" class="form-check-input eRadioSuccess"
                        id="status_active" required checked>
                    <label for="status_active" class="form-check-label">{{ get_phrase('Active') }}</label>
                </div>
                <div class="form-check">
                    <input type="radio" value="draft" name="status" class="form-check-input eRadioSecondary"
                        id="status_draft" required>
                    <label for="status_draft" class="form-check-label">{{ get_phrase('Draft') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="fpb-7 mb-3">
        <button type="submit" class="ol-btn-primary">{{ get_phrase('Add New Assignment') }}</button>
    </div>

</form>
@include('admin.init')
