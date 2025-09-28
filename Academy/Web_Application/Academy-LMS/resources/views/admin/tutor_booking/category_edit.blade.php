@php
    $category = App\Models\TutorCategory::where('id', $id)->first();
@endphp
<form action="{{ route('admin.tutor_category_update', $category->id) }}" method="post" enctype="multipart/form-data">
    @CSRF

    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="category_name" class="form-label ol-form-label">{{ get_phrase('Category Name') }}</label>
                <input type="text" name="name" value="{{ $category->name }}" class="form-control ol-form-control" id="category_name" placeholder="{{ get_phrase('Enter your category name') }}" aria-label="{{ get_phrase('Enter your unique category name') }}" required />
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
