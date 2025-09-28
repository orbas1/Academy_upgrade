<label for="subject_id" class="form-label ol-form-label">
    {{ get_phrase('Subject') }}<span class="text-danger ms-1">*</span>
</label>
<select class="ol-select2" name="subject_id" id="subject_id" required>
    <option value="">{{ get_phrase('Select a subject') }}</option>

    @foreach ($teaches as $teach)
        <option value="{{ $teach->subject_id }}">{{ $teach->category_to_tutorSubjects->name }}</option>
    @endforeach
</select>

@include('instructor.init')
