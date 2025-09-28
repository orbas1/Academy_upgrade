<div class="row mb-3">
    <label for="email" class="form-label ol-form-label col-sm-2 col-form-label">{{ get_phrase('Email') }}<span
            class="text-danger ms-1">*</span></label>
    <div class="col-sm-8">
        <input type="email" name="email" class="form-control ol-form-control" id="email" @isset($instructor->email) value="{{ $instructor->email }}" @endisset required>
    </div>
</div>
<div class="row mb-3">
    <div class="col-sm-8 offset-sm-2">
        <input type="hidden" name="email_verified" value="0">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="email_verified" name="email_verified" value="1">
            <label class="form-check-label" for="email_verified">{{ get_phrase('Mark email as verified') }}</label>
        </div>
    </div>
</div>

@if(!isset($instructor->email))
    <div class="row mb-3">
        <label for="password" class="form-label ol-form-label col-sm-2 col-form-label">{{ get_phrase('Password') }}<span
                class="text-danger ms-1">*</span></label>
        <div class="col-sm-8">
            <input type="password" name="password" class="form-control ol-form-control" id="password">
        </div>
    </div>
@endisset
