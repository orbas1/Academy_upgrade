<input type="hidden" name="lesson_type" value="scorm">

<div class="form-group mb-3">
    <label class="form-label ol-form-label">{{ get_phrase('Select scorm provider') }}</label>
    <select class="form-control ol-select2" data-toggle="select2" name="scorm_provider" required>
        <option value="">{{ get_phrase('Select a provider') }}</option>
        <option value="iSpring">{{ get_phrase('iSpring') }}</option>
        <option value="articulate">{{ get_phrase('Articulate') }}</option>
        <option value="adobeCaptivate">{{ get_phrase('Adobe Captivate') }}</option>
    </select>
</div>

<div class="form-group mb-2">
    <label class="form-label ol-form-label">{{ get_phrase('Upload scorm file') }}</label>
    <div class="input-group">
        <div class="custom-file w-100">
            <input type="file" class="form-control ol-form-control" id="scorm_file" name="scorm_file" accept=".zip"
                required>
        </div>
    </div>
</div>

<script>
    "use strict";
    initializeDurationPickers([".duration-picker"]);
</script>
