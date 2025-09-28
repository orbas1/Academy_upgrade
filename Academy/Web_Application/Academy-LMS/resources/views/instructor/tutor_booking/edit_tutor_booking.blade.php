@php
    $booking_details = App\Models\TutorBooking::find($id);
    $categories = App\Models\TutorCategory::where('parent', 0)->orderBy('name', 'asc')->get();
@endphp
<form action="{{ route('instructor.booking_status_update', ['status' => 'update', 'id' => $id]) }}" method="post" enctype="multipart/form-data">
    @CSRF

    <div class="mb-3">
        <label for="title" class="form-label ol-form-label">
            {{ get_phrase('Tution topic') }}<span class="text-danger ms-1">*</span>
        </label>
        <input type="text" class="form-control ol-form-control" id="title" name="title" placeholder="Topic title" value="{{ $booking_details->title }}" required>
    </div>

    <div class="mb-3">
        <label for="category_id" class="form-label ol-form-label">
            {{ get_phrase('Category') }}<span class="text-danger ms-1">*</span>
        </label>
        <select class="ol-select2" name="category_id" id="category_id" required>
            <option value="0">{{ get_phrase('Select Category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ $category->id == $booking_details->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3 load_categories">
        <label for="sub_category_id" class="form-label ol-form-label">
            {{ get_phrase('Sub Category') }}<span class="text-danger ms-1">*</span>
        </label>
        <select class="ol-select2" id="sub_category_id" name="sub_category_id" required>
            @if($booking_details->sub_category_id != 0)
                <option value="{{ $booking_details->sub_category_id }}">{{ get_phrase($booking_details->sub_category->name) }}</option>
            @else
                <option value="0"><?php echo get_phrase('No category found'); ?></option>
            @endif
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label ol-form-label col-sm-2 w-100">
            {{ get_phrase('Class type') }}
            <span class="text-danger ms-1">*</span>
        </label>
        <div class="ol-radio-wrap d-flex flex-wrap">
            <div class="form-check form-check-radio">
                <input type="radio" class="form-check-input form-check-input-radio" id="online" name="class_type" value="1" @if($booking_details->tution_class_type == '1') checked @endif required>
                <label for="online" class="form-check-label">{{ get_phrase('Online') }}</label>
            </div>

            <div class="form-check form-check-radio">
                <input type="radio" class="form-check-input form-check-input-radio" id="in_person" name="class_type" value="2" @if($booking_details->tution_class_type == '2') checked @endif required>
                <label for="in_person" class="form-check-label">{{ get_phrase('In person') }}</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label ol-form-label col-sm-2 w-100">
            {{ get_phrase('Price type') }}
            <span class="text-danger ms-1">*</span>
        </label>
        <div class="ol-radio-wrap d-flex flex-wrap">
            <div class="form-check form-check-radio">
                <input type="radio" class="form-check-input form-check-input-radio" id="fixed" name="price_type" value="fixed" @if($booking_details->price_type == 'fixed') checked @endif required>
                <label for="fixed" class="form-check-label">{{ get_phrase('Fixed') }}</label>
            </div>

            <div class="form-check form-check-radio">
                <input type="radio" class="form-check-input form-check-input-radio" id="hourly" name="price_type" value="hourly" @if($booking_details->price_type == 'hourly') checked @endif required>
                <label for="hourly" class="form-check-label">{{ get_phrase('Hourly') }}</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label ol-form-label col-sm-2 w-100">
            {{ get_phrase('Price') }}
            <span class="text-danger ms-1">*</span>
        </label>
        <input type="number" class="form-control ol-form-control" name="price" id="price" min="0" step="any" placeholder="Put a price" value="{{ $booking_details->price }}" required>
    </div>

    <div class="mb-3">
        <label for="meeting_link" class="form-label ol-form-label">
            {{ get_phrase('Class invitation link') }}<span class="text-danger ms-1"></span>
        </label>
        <input type="text" class="form-control ol-form-control" id="meeting_link" name="meeting_link" placeholder="zoom invitation link" value="{{ $booking_details->meeting_link }}">
    </div>

    <div class="mb-2">
        <button class="btn ol-btn-primary">{{ get_phrase('Update booking') }}</button>
    </div>
</form>

<script type="text/javascript">
    "use strict";

    $('.ol-select2').select2({
        dropdownParent: $("#ajaxModal")
    });

    $(document).ready(function() {

        // load courses based on privacy
        $('select[name="category_id"]').on('change', function() {
            let category_id = $(this).val()

            $.ajax({
                type: "get",
                url: "{{ route('instructor.get.subject_subcategory_by_id') }}",
                data: {
                    category_id: category_id
                },
                success: function(response) {
                    if (response) {
                        $('.load_categories').empty().append(response);
                    }
                },
                error: function(xhr, status, error) {
                    error(error);
                }
            });
        });
    });
</script>


@include('instructor.init')