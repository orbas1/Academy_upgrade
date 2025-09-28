<form action="{{ route('admin.tutor_subject_store') }}" method="post" enctype="multipart/form-data">
    @CSRF

    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="name" class="form-label ol-form-label">{{ get_phrase('Subject Name') }}</label>
                <input type="text" name="name" class="form-control ol-form-control" id="name" placeholder="{{ get_phrase('Enter subject name') }}" aria-label="{{ get_phrase('Enter subject name') }}" required />
            </div>

            <div class="mb-2">
                <button class="btn ol-btn-primary">{{ get_phrase('Submit') }}</button>
            </div>
        </div>
    </div>
</form>
