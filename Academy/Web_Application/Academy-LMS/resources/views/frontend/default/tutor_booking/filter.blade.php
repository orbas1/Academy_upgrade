@php
    $highestFee = App\Models\TutorCanTeach::max('price');
    $lowestFee = App\Models\TutorCanTeach::min('price');

    $min_fee = request()->input('min_fee') ?? $lowestFee;
    $max_fee = request()->input('max_fee') ?? $highestFee;
@endphp
<div class="offcanvas-lg offcanvas-start lms1-category-offcanvas" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasSidebarLabel"></h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasSidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="lms1-category-sidebar">
            <form action="{{ route('tutor_list') }}" method="get"
                id="filter-tutors">

                @if (request()->has('search'))
                    <input type="hidden" name="search" value="{{ request()->input('search') }}">
                @endif
                
                <ul>
                    <li class="side-accordion-item active">
                        <h4 class="side-accordion-title in-title-16px">{{ get_phrase('Categories') }}</h4>
                        <div class="side-accordion-body" style="display: block;">
                            <ul class="d-flex flex-column gap-3">
                                @foreach($categories as $category)
                                <li>
                                    <div class="form-check form-checkbox">
                                        <input class="form-check-input form-checkbox-input" type="radio" name="category" value="{{ $category->slug }}" id="category-{{ $category->id }}" @if (request()->has('category') && request()->input('category') == $category->slug) checked @endif>
                                        <label class="form-check-label form-checkbox-label" for="category-{{ $category->id }}">
                                            <span>{{ $category->name }}</span>
                                            <span>({{ $category->category_to_tutorSchedule->count() }})</span>
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    <li class="side-accordion-item">
                        <h4 class="side-accordion-title in-title-16px">{{ get_phrase('Subjects') }}</h4>
                        <div class="side-accordion-body">
                            <ul class="d-flex flex-column gap-3">
                                @foreach($subjects as $subject)
                                <li>
                                    <div class="form-check form-checkbox">
                                        <input class="form-check-input form-checkbox-input" type="radio" name="subject" value="{{ $subject->slug }}" id="subject-{{ $subject->id }}" @if (request()->has('subject') && request()->input('subject') == $subject->slug) checked @endif>
                                        <label class="form-check-label form-checkbox-label" for="subject-{{ $subject->id }}">
                                            <span>{{ $subject->name }}</span>
                                            <span>({{ $subject->subject_to_tutorSchedule->count() }})</span>
                                        </label>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    <!-- Range -->
                    <li class="side-accordion-price py-3">
                        <h4 class="in-title-16px mb-3">{{ get_phrase('Fee') }}</h4>
                        <div>
                            <div class="lms-slider-range-wrapper mb-10px">
                                <div id="lms-slider-range"></div>
                            </div>
                            <div class="d-flex align-items-center gap-8 flex-wrap justify-content-between">
                                <p class="slider-range-value">{{ get_phrase('Min') }}: $<span class="from-slider-value2">{{ $lowestFee }}</span></p>
                                <p class="slider-range-value">{{ get_phrase('Max') }}: $<span class="to-slider-value2">{{ $highestFee }}</span></p>
                                <!-- Hidden inputs for min and max -->
                                <input type="text" class="from-slider-value form-control" name="min_fee" value="{{ $min_fee }}" hidden/>
                                <input type="text" class="to-slider-value form-control" name="max_fee" value="{{ $max_fee }}" hidden/>
                            </div>
                        </div>
                    </li>
                    <li class="side-accordion-item">
                        <h4 class="side-accordion-title in-title-16px">{{ get_phrase('Ratings') }}</h4>
                        <div class="side-accordion-body">
                            <ul class="d-flex flex-column gap-3">
                                @for ($i = 5; $i >= 1; $i--)
                                    <li>
                                        <input class="form-check-input" type="radio" name="rating" value="{{ $i }}"
                                            id="raging-{{ $i }}" @if (request()->has('rating') && request()->input('rating') == $i) checked @endif />
                                        <label class="form-check-label" for="raging-{{ $i }}">
                                            <ul class="d-flex g-star g-5">
                                                @for ($j = 1; $j <= 5; $j++)
                                                    <li @if ($j <= $i) class="color-g" @endif>
                                                        <i class="fa fa-star"></i>
                                                    </li>
                                                @endfor
                                            </ul>
                                        </label>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    </li>
                </ul>
            </form>
        </div>
    </div>
</div>


@push('js')
    <script>

        "use strict";

        $(document).ready(function() {
            // Trigger form submission when a radio button changes
            $('input[type="radio"]').change(function(e) {
                $('#filter-tutors').trigger('submit');
            });

            var minFee = {{ $min_fee }};
            var maxFee = {{ $max_fee }};

            // Initialize the slider with independent handle movement
            $("#lms-slider-range").slider({
                range: true,
                min: {{ $lowestFee }},
                max: {{ $highestFee }},
                values: [minFee, maxFee],
                slide: function(event, ui) {
                    // Ensure the min handle doesn't exceed the max handle and vice versa
                    if (ui.values[0] >= ui.values[1]) {
                        return false;
                    }

                    // Update the displayed values during the slide
                    $(".from-slider-value2").text(ui.values[0]);
                    $(".to-slider-value2").text(ui.values[1]);
                    $(".from-slider-value").val(ui.values[0]);
                    $(".to-slider-value").val(ui.values[1]);
                },
                change: function(event, ui) {
                    // Update the hidden inputs with the new values
                    $(".from-slider-value").val(ui.values[0]);
                    $(".to-slider-value").val(ui.values[1]);

                    // Trigger form submission
                    $('#filter-tutors').trigger('submit');
                }
            });
            // Set initial values in the display elements on page load
            $(".from-slider-value2").text(minFee);
            $(".to-slider-value2").text(maxFee);
        });
    </script>

@endpush