@php
    $assignment = App\Models\Assignment::where('id', $assignment_id)->first();
@endphp

<form id="ajaxForm" method="POST">
    @csrf
    <input type="hidden" name="submitted_assignment" value="{{ $submitted_assignment }}">
    <input type="hidden" name="assignment_id" value="{{ $assignment_id }}">
    <input type="hidden" name="course_id" value="{{ $course_id }}">
    <input type="hidden" name="status" value="1">

    <div class="fpb-7 mb-3">
        <label for="marks" class="form-label ol-form-label">
            {{ get_phrase('Marks') }}<span><span>(</span>{{ get_phrase('out of') }}
                {{ $assignment->total_marks }}<span>)</span></span><span class="required">*</span>
        </label>
        <input type="number" name="marks" class="form-control ol-form-control" id="marks" required>
    </div>

    <div class="fpb-7 mb-3">
        <label for="remarks" class="form-label ol-form-label">{{ get_phrase('Remarks') }}</label>
        <textarea class="form-control ol-form-control" name="remarks" id="remarks" rows="4"></textarea>
    </div>

    <div class="fpb-7 mb-3">
        <button type="submit" data-bs-dismiss="modal" class="btn btn-primary">{{ get_phrase('Submit') }}</button>
    </div>
</form>

@include('admin.init')
@include('admin.common_scripts')

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#ajaxForm').submit(function(e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.assignment.review') }}',
                data: formData,
                success: function(response) {
                    if (response.redirect_url) {
                        loadView(response.redirect_url,
                            '#view_submission');
                    }
                },
            });
        });
    });
</script>
