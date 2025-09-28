@php
    $countries = App\Models\Country::all();
    $educations = json_decode(auth()->user()->educations, true) ?? [];
    
    // Check if the specified index exists and get the education data
    $education = isset($educations[$index]) ? $educations[$index] : null; 
@endphp

@if ($education)  <!-- Check if education data exists -->
<form class="ajaxFormSubmission" action="{{ route('instructor.manage.education_update', ['index' => $index]) }}" method="post" enctype="multipart/form-data">
    @csrf

    <div class="form-group mb-3">
        <label class="form-label ol-form-label">{{ get_phrase('Title') }}</label>
        <input type="text" name="title" class="form-control ol-form-control" value="{{ $education['title'] }}" required>
    </div>
    
    <div class="form-group mb-3">
        <label class="form-label ol-form-label">{{ get_phrase('Institute') }}</label>
        <input type="text" name="institute" class="form-control ol-form-control" value="{{ $education['institute'] }}" required>
    </div>

    <!-- Country and City in the same row -->
    <div class="row">
        <div class="col-md-6 form-group mb-3">
            <label class="form-label ol-form-label">{{ get_phrase('Country') }}</label>
            <select class="form-control ol-select2" data-toggle="select2" name="country" required>
                <option value="">{{ get_phrase('Select a country') }}</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->name }}" @if($education['country'] == $country->name) selected @endif>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 form-group mb-3">
            <label class="form-label ol-form-label">{{ get_phrase('City') }}</label>
            <input type="text" name="city" class="form-control ol-form-control" value="{{ $education['city'] }}" required>
        </div>
    </div>

    <!-- Start Date and End Date in the same row -->
    <div class="row">
        <div class="col-md-6 form-group mb-3">
            <label class="form-label ol-form-label" for="start_date">{{ get_phrase('Start Date') }}</label>
            <input type="date" name="start_date" id="start_date" class="form-control ol-form-control" value="{{ $education['start_date'] }}">
        </div>

        <div class="col-md-6 form-group mb-3">
            <label class="form-label ol-form-label" for="end_date">{{ get_phrase('End Date') }}</label>
            <input type="date" name="end_date" id="end_date" class="form-control ol-form-control" value="{{ $education['end_date'] ?? '' }}">
        </div>
    </div>

    <div class="form-group mb-3">
        <input type="checkbox" name="status" id="status" value="ongoing" class="form-check-input" @if($education['status'] === 'ongoing') checked @endif>
        <label for="status" class="form-label ol-form-label">{{ get_phrase('This degree/course is currently ongoing') }}</label>
    </div>

    <div class="form-group mb-3">
        <label class="form-label ol-form-label">{{ get_phrase('Description') }}</label>
        <textarea name="description" class="form-control text_editor">{{ $education['description'] }}</textarea>
    </div>

    <div class="text-center">
        <button class="btn ol-btn-primary ol-btn-sm w-100 formSubmissionBtn" type="submit" name="button">{{ get_phrase('Save') }}</button>
    </div>
</form>
@else
    <p>{{ get_phrase('Education data not found.') }}</p>
@endif

@include('instructor.init')
