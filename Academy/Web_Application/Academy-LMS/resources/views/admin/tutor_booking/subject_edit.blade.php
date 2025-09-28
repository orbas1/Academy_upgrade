@php
    $subject = App\Models\TutorSubject::where('id', $id)->first();
@endphp
<form action="{{ route('admin.tutor_subject_update', $subject->id) }}" method="post" enctype="multipart/form-data">
    @CSRF

    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="subject_name" class="form-label ol-form-label">{{ get_phrase('subject Name') }}</label>
                <input type="text" name="name" value="{{ $subject->name }}" class="form-control ol-form-control" id="subject_name" placeholder="{{ get_phrase('Enter your subject name') }}" aria-label="{{ get_phrase('Enter your unique subject name') }}" required />
            </div>

            <div class="mb-2">
                <button class="btn ol-btn-primary">{{ get_phrase('Submit') }}</button>
            </div>
        </div>
    </div>
</form>


<script type="text/javascript">
    "use strict";

    $('.ol-select2').select2({
        dropdownParent: $("#ajaxModal")
    });
</script>
