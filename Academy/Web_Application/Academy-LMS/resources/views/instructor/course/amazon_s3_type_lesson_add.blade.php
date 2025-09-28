
<input type="hidden" name="lesson_provider" value="amazon_s3">
<input type="hidden" name="lesson_type" value="amazon_s3">

<div class="form-group mb-2">
    <label class="form-label ol-form-label">{{ get_phrase('Upload video file') }}</label>
    <div class="input-group">
        <div class="custom-file w-100">
            <input type="file" class="form-control ol-form-control" id="amazon_s3_video" name="amazon_s3_video" required>
        </div>
    </div>
</div>

<div class="form-group mb-2">
    <label class="form-label ol-form-label">{{ get_phrase('Duration') }}</label>
    <input class="form-control ol-form-control duration_picker" name="duration">
</div>


<script>
    "use strict";
    initializeDurationPickers([".duration_picker"]);
</script>