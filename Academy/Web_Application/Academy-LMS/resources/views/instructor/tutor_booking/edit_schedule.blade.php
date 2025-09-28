@extends('layouts.instructor')

@push('title', get_phrase('Schedule Create'))

@push('meta')
@endpush

@push('css')
@endpush



@section('content')
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-12px px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    {{ get_phrase('Update schedule') }}
                </h4>
                <a href="{{ url()->previous() }}" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px ms-auto">
                    <span class="fi-rr-arrow-left"></span>
                    <span>{{ get_phrase('Back') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="ol-body-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-7">
                    <div class="ol-card">
                        <form class="required-form" action="{{ route('instructor.schedule_update', ['id' => $schedule_details->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="ol-card-body mb-20px p-20px">
                                <h4 class="title fs-16px mb-3">{{ get_phrase('Edit schedule') }}</h4>

                                <div class="row">
                                    <div class="col-xxl-6">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label ol-form-label">
                                                {{ get_phrase('Category') }}<span class="text-danger ms-1">*</span>
                                            </label>
                                            <select class="ol-select2" name="category_id" id="category_id" required>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->category_id }}" @if($schedule_details->category_id == $category->category_id) selected @endif>{{ $category->category_to_tutorCategory->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    @php
                                    $canTeach = App\Models\TutorCanTeach::where('category_id', $schedule_details->category_id)->get();
                                    @endphp

                                    <div class="col-xxl-6 d-flex align-items-end">
                                        <div class="w-100 mb-3 load_subjects">
                                            <label for="subject_id" class="form-label ol-form-label">
                                                {{ get_phrase('Subject') }}<span class="text-danger ms-1">*</span>
                                            </label>
                                            <select class="ol-select2" name="subject_id" id="subject_id" required>
                                                @foreach ($canTeach as $teach)
                                                    <option value="{{ $teach->subject_id }}" @if($schedule_details->subject_id == $teach->subject_id) selected @endif>{{ $teach->category_to_tutorSubjects->name }}</option>
                                                @endforeach
                                            </select>
                                            
                                        </div>
                                    </div>

                                </div>

                                <div class="eRadios">
                                    <div class="paid-section" id="paid-section">

                                        <div class="mb-3">
                                            <label class="form-label ol-form-label" for="start_time">{{ get_phrase('Schedule start time') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="datetime-local" class="form-control ol-form-control" id="start_time" name="start_time" value="{{ date('Y-m-d\TH:i', $schedule_details->start_time) }}" required>
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
                                            <option value="{{ $i }}" {{ $schedule_details->duration == $i ? 'selected' : '' }}>
                                                {{ intdiv($i, 60) }} hour{{ intdiv($i, 60) > 1 ? 's' : '' }}{{ $i % 60 > 0 ? ' ' . ($i % 60) . ' minutes' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    
                                </div>

                                <div class="mb-3">
                                    <label class="form-label ol-form-label" for="description">{{ get_phrase('Description') }}</label>
                                    <textarea name="description" placeholder="{{ get_phrase('Enter Description') }}" class="form-control ol-form-control text_editor">{{ $schedule_details->description }}</textarea>
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
        $('form.required-form').find('input, select').each(function() {
            if ($(this).prop('required')) {
                if ($(this).val() === "") {
                    pass = 0;
                }
            }
        });


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

</script>

@endpush