@php
    $categories = App\Models\TutorCategory::orderBy('name', 'asc')->get();
    $subjects = App\Models\TutorSubject::orderBy('name', 'asc')->get();
@endphp
<form action="{{ route('instructor.my_subject_store') }}" method="post" enctype="multipart/form-data">
    @CSRF

    <div class="row">
        <div class="col-12">

            <div class="mb-3">
                <label for="category_id" class="form-label ol-form-label">
                    {{ get_phrase('Category') }}<span class="text-danger ms-1">*</span>
                </label>
                <select class="form-control ol-form-control ol-select2" name="category_id" id="category_id" required>
                    <option value="0">{{ get_phrase('Select Category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="subject_id" class="form-label ol-form-label">
                    {{ get_phrase('Subject') }}<span class="text-danger ms-1">*</span>
                </label>
                <select class="form-control ol-form-control ol-select2" name="subject_id" id="subject_id" required>
                    <option value="0">{{ get_phrase('Select Subject') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label ol-form-label">{{ get_phrase('Price') }}
                    <small>({{ currency() }})</small><span class="text-danger ms-1">*</span></label>

                <input type="number" name="price" class="form-control ol-form-control" id="price" min="1" placeholder="{{ get_phrase('Enter your course price') }} ({{ currency() }})">
            </div>

            <div class="mb-3">
                <label class="form-label ol-form-label" for="description">{{ get_phrase('Description') }}</label>
                <textarea name="description" placeholder="{{ get_phrase('Enter Description') }}" class="form-control ol-form-control text_editor"></textarea>
            </div>

            <div class="mb-3">
                <label for="thumbnail" class="form-label ol-form-label">{{ get_phrase('Thumbnail') }}</label>
                <input type="file" name="thumbnail" class="form-control ol-form-control" id="thumbnail" accept="image/*" />
            </div>

            <div class="mb-2">
                <button class="btn ol-btn-primary">{{ get_phrase('Submit') }}</button>
            </div>
        </div>
    </div>
</form>

@include('instructor.init')
