@extends('layouts.instructor')

@push('title', get_phrase('Schedule Create'))

@push('meta')
@endpush

@push('css')
@endpush



@section('content')
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-18px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('Add schedule') }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="ol-body-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-7">
                    <div class="ol-card">
                        <form class="required-form" action="{{ route('instructor.schedule_store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="ol-card-body mb-20px p-20px">
                                <h4 class="title fs-16px mb-3">{{ get_phrase('Add schedule') }}</h4>

                                <div class="row">
                                    <div class="col-xxl-6">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label ol-form-label">
                                                {{ get_phrase('Category') }}<span class="text-danger ms-1">*</span>
                                            </label>
                                            <select class="ol-select2" name="category_id" id="category_id" required>
                                                <option value="0">{{ get_phrase('Select Category') }}</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->category_id }}">{{ $category->category_to_tutorCategory->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-xxl-6 d-flex align-items-end">
                                        <div class="w-100 mb-3 load_subjects">
                                            <label for="subject_id" class="form-label ol-form-label">
                                                {{ get_phrase('Subject') }}<span class="text-danger ms-1">*</span>
                                            </label>
                                            <select class="ol-select2" name="subject_id" id="subject_id" required>
                                                <option value="">{{ get_phrase('First select category') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="eRadios">
                                    <div class="paid-section" id="paid-section">

                                        <div class="mb-3">
                                            <label class="form-label ol-form-label col-sm-2 w-100">
                                                {{ get_phrase('Tution type') }}
                                                <span class="text-danger ms-1">*</span>
                                            </label>
                                            <div class="d-flex gap-3">

                                                <div class="form-check">
                                                    <input type="radio" name="tution_type" value="1"
                                                        class="form-check-input form-check-input-radio" id="single_1" name="1_tution_type_indentify"
                                                        onchange="$('#tution_type').slideUp(200)" checked>
                                                    <label for="single_1"
                                                        class="form-check-label">{{ get_phrase('Single time') }}</label>
                                                </div>

                                                <div class="form-check">
                                                    <input type="radio" name="tution_type" value="0"
                                                        class="form-check-input form-check-input-radio" id="selected_class_1" name="1_tution_type_indentify"
                                                        onchange="$('#tution_type').slideDown(200)">
                                                    <label for="selected_class_1"
                                                        class="form-check-label">{{ get_phrase('Repeated days') }}</label>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label ol-form-label" for="start_time">{{ get_phrase('Schedule start time') }}<span class="required">*</span></label>
                                            <input type="datetime-local" class="form-control ol-form-control" id="start_time" name="start_time" required>
                                        </div>

                                        <div id="tution_type">

                                            <div class="mb-3">
                                                <label class="form-label ol-form-label"
                                                    for="end_time">{{ get_phrase('Schedule end Date') }}</label>
                                                <input type="date" name="end_time" id="end_time" class="form-control ol-form-control">
                                            </div>

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <div> 
                                                        <label class="form-label ol-form-label col-sm-2 w-100">
                                                            {{ get_phrase('Select days') }}
                                                            <span class="text-danger ms-1">*</span>
                                                        </label>
                                                    </div>
                                                    <div class="d-flex" style="justify-content: right;"> 
                                                        <label class="form-label ol-form-label col-sm-2 w-100 float-end" for="check_all_days">{{ get_phrase('Check all') }}</label><input type="checkbox" class="form-check-input form-check-input-checkbox" id="check_all_days">
                                                    </div>
                                                </div>
                                                <div class="form-check form-check-checkbox d-flex justify-content-between">
                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="sunday_1" name="1_day[]" value="sunday">
                                                        <label id="label_sunday_1" for="sunday_1"><?php echo get_phrase('sunday'); ?></label> 
                                                    </div>
                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="monday_1" name="1_day[]" value="monday">
                                                        <label id="label_monday_1" for="monday_1"><?php echo get_phrase('monday'); ?></label>
                                                    </div>

                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="tuesday_1" name="1_day[]" value="tuesday">
                                                        <label id="label_tuesday_1" for="tuesday_1"><?php echo get_phrase('tuesday'); ?></label>
                                                    </div>

                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="wednesday_1" name="1_day[]" value="wednesday">
                                                        <label id="label_wednesday_1" for="wednesday_1"><?php echo get_phrase('wednesday'); ?></label>
                                                    </div>

                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="thursday_1" name="1_day[]" value="thursday">
                                                        <label id="label_thursday_1" for="thursday_1"><?php echo get_phrase('thursday'); ?></label>
                                                    </div>

                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="friday_1" name="1_day[]" value="friday">
                                                        <label id="label_friday_1" for="friday_1"><?php echo get_phrase('friday'); ?></label>
                                                    </div>

                                                    <div>
                                                        <input type="checkbox" class="form-check-input form-check-input-checkbox" id="saturday_1" name="1_day[]" value="saturday">
                                                        <label id="label_saturday_1" for="saturday_1"><?php echo get_phrase('saturday'); ?></label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        </div>

                                    </div>
                                </div>

                                <!-- Select with Search -->
                                <div class="mb-3">
                                    <label class="form-label ol-form-label col-sm-2 w-100">
                                        {{ get_phrase('Class Duration') }}
                                        <span class="text-danger ms-1">*</span>
                                    </label>
                                    <select class="ol-select2" id="duration" name="duration">
                                        <option value="0">{{ __('Select Duration') }}</option>
                                        @for ($i = 60; $i <= 300; $i += 15)
                                            <option value="{{ $i }}">
                                                {{ intdiv($i, 60) }} hour{{ intdiv($i, 60) > 1 ? 's' : '' }}{{ $i % 60 > 0 ? ' ' . ($i % 60) . ' minutes' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    
                                </div>

                                <div class="mb-3">
                                    <label class="form-label ol-form-label" for="description">{{ get_phrase('Description') }}</label>
                                    <textarea name="description" placeholder="{{ get_phrase('Enter Description') }}" class="form-control ol-form-control text_editor"></textarea>
                                </div>
                                
                                <button type="button" class="btn ol-btn-primary" onclick="checkRequiredFields_schedules()"><?php echo get_phrase('Save Schedule'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')

<script>
    "use strict";

    function checkRequiredFields_schedules() {
        var pass = 1;
        var check = 0;
        $('form.required-form').find('input, select, radio').each(function() {
            if ($(this).prop('required')) {
                if ($(this).val() === "") {
                    pass = 0;
                }
            }
        });


        if ($('#selected_class_1').is(':checked')) {
            if ($('#sunday_1').is(':checked') || $('#monday_1').is(':checked') || $('#tuesday_1').is(':checked') || $('#wednesday_1').is(':checked') || $('#thursday_1').is(':checked') || $('#friday_1').is(':checked') || $('#saturday_1').is(':checked')) {

                pass = 1;
            } else {
                pass = 0;
            }


        }


        var duration = $('#duration').val();

        if (duration == 0) {
            pass = 0;

        }


        if (pass === 1) {
            $('form.required-form').submit();
        } else {
            //error_required_field();
            error('{{ get_phrase("You can not keep any field empty") }}');
        }


    }

    $(document).ready(function() {

        $("#tution_type").hide();

        // load courses based on privacy
        $('select[name="category_id"]').on('change', function() {
            let category_id = $(this).val()

            $.ajax({
                type: "get",
                url: "{{ route('instructor.get.subject_by_category_id') }}",
                data: {
                    category_id: category_id
                },
                success: function(response) {
                    if (response) {
                        $('.load_subjects').empty().append(response);
                    }
                },
                error: function(xhr, status, error) {
                    error(error);
                }
            });
        });
    });

    $('#check_all_days').on('change', function() {
        var checked = this.checked


        if (checked == true) {
            $('#sunday_1').prop('checked', true);
            $('#monday_1').prop('checked', true);
            $('#tuesday_1').prop('checked', true);
            $('#wednesday_1').prop('checked', true);
            $('#thursday_1').prop('checked', true);
            $('#friday_1').prop('checked', true);
            $('#saturday_1').prop('checked', true);
        }

        if (checked == false) {
            $('#sunday_1').prop('checked', false);
            $('#monday_1').prop('checked', false);
            $('#tuesday_1').prop('checked', false);
            $('#wednesday_1').prop('checked', false);
            $('#thursday_1').prop('checked', false);
            $('#friday_1').prop('checked', false);
            $('#saturday_1').prop('checked', false);
        }
    });

    $('#sunday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#monday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#tuesday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#wednesday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#thursday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#friday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });
    $('#saturday_1').on('change', function() {
        var checked = this.checked

        if (checked == false) {
            $('#check_all_days').prop('checked', false);
        }

    });

</script>

@endpush