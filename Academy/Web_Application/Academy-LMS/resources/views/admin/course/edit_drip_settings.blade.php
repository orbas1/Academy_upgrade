@php

    $drip_content_settings = json_decode($course_details->drip_content_settings, true);

@endphp

<div class="row mb-3 ">
    <label for="enable_drip_content" class="form-label ol-form-label col-sm-3 col-form-label">{{ get_phrase('Drip content') }} <span
            class="text-danger ms-1">*</span></label>
    <div class="col-sm-9">
        <div class="eRadios">
            <div class="form-check">
                <input type="radio" value="0" name="enable_drip_content" class="form-check-input eRadioSuccess"
                    id="drip_off" @if ($course_details->enable_drip_content == 0) checked @endif required>
                <label for="drip_off" class="form-check-label">{{ get_phrase('Off') }}</label>
            </div>

            <div class="form-check">
                <input type="radio" value="1" name="enable_drip_content" class="form-check-input eRadioPrimary"
                    id="drip_on" @if ($course_details->enable_drip_content == 1) checked @endif required>
                <label for="drip_on" class="form-check-label">{{ get_phrase('On') }}</label>
            </div>

        </div>
    </div>
</div>

<div class="row mb-3">
	<label class="form-label ol-form-label col-sm-3 col-form-label">{{get_phrase('Lesson completion role')}}<span class="text-danger ms-1">*</span></label>
	<div class="col-sm-9">
		<div class="eRadios">
			<div class="form-check">
				<input type="radio" onchange="$('.toggleMinimumWatchField').toggleClass('d-hidden');" value="percentage" id="video_percentage_wise" name="lesson_completion_role" class="form-check-input eRadioSuccess" {{ $drip_content_settings['lesson_completion_role'] == 'percentage' ? 'checked' : '' }}>
				<label for="video_percentage_wise" class="form-check-label">{{get_phrase('Video percentage wise')}}</label>
			</div>

			<div class="form-check">
				<input type="radio" onchange="$('.toggleMinimumWatchField').toggleClass('d-hidden');" id="video_duration_wise" name="lesson_completion_role" value="duration" class="form-check-input eRadioSuccess" {{ $drip_content_settings['lesson_completion_role'] == 'duration' ? 'checked' : '' }}>
				<label for="video_duration_wise" class="form-check-label">{{get_phrase('Video duration wise')}}</label>
			</div>
		</div>
	</div>
</div>

<div class="paid-section toggleMinimumWatchField {{ $drip_content_settings['lesson_completion_role'] != 'percentage' ? 'd-hidden' : '' }}">
	<div class="row mb-3">
		<label for="minimum_percentage" class="form-label ol-form-label col-sm-3 col-form-label">{{get_phrase('Minimum percentage to watch')}}(%)<span class="text-danger ms-1">*</span></label>

		<div class="col-sm-9">
			<input type="text" value="{{ $drip_content_settings['minimum_percentage'] }}" class="form-control ol-form-control" id="minimum_percentage" name="minimum_percentage" placeholder="50">
		</div>
	</div>
</div>

<div class="paid-section toggleMinimumWatchField {{ $drip_content_settings['lesson_completion_role'] != 'duration' ? 'd-hidden' : '' }}">
	<div class="row mb-3">
		<label for="minimum_duration" class="form-label ol-form-label col-sm-3 col-form-label">{{get_phrase('Minimum duration to watch')}}<span class="text-danger ms-1">*</span></label>

		<div class="col-sm-9">
			<input type="text" value="{{ seconds_to_time_format($drip_content_settings['minimum_duration']) }}" class="form-control ol-form-control" id="minimum_duration" name="minimum_duration" placeholder="00:01:10">
		</div>
	</div>
</div>

<div class="row mb-3">
    <label class="form-label ol-form-label col-sm-3 col-form-label" for="description">{{get_phrase('Message for locked lesson')}}</label>
    <div class="col-sm-9">
        <textarea id="locked_lesson_message" name="locked_lesson_message" placeholder="{{ get_phrase('Enter Description') }}" class="form-control ol-form-control text_editor">{!! $drip_content_settings['locked_lesson_message'] !!}</textarea>
    </div>
</div>