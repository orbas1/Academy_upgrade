{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="cooking-section-title">
                    <h3 class="title-5 fs-32px lh-42px fw-600 text-center mb-20 builder-editable" builder-identity="1">{{ get_phrase('Featured Courses') }}</h3>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('Those course highlights a handpicked course with exceptional content or exclusive offerings.') }}</p>
                </div>
            </div>
        </div>
        <div class="row row-28 mb-110">
            @php
                $featured_courses = DB::table('courses')->where('status', 'active')->latest('id')->take(4)->get();
            @endphp
            @foreach ($featured_courses->take(4) as $key => $row)
                @php
                    $ratings = DB::table('reviews')->where('course_id', $row->id)->pluck('rating')->toArray();
                    $average_rating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : null;
                @endphp
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="cooking-course-link">
                        <div class="cooking-course-card">
                            <div class="banner">
                                <img src="{{ get_image($row->thumbnail) }}" alt="">
                            </div>
                            <div class="cooking-course-card-body">
                                <h5 class="title ellipsis-line-2">{{ $row->title }}</h5>
                                <div class="time-rating d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="time-wrap d-flex align-items-center">
                                        <img src="{{ asset('assets/frontend/default/image/clock-gray-20.svg') }}" alt="">
                                        <p class=time>{{ total_durations('duration') }}</p>
                                    </div>
                                    <div class="rating-wrap d-flex align-items-center">
                                        @if ($average_rating !== null)
                                            <p class="rating">{{ number_format($average_rating, 1) }}</p>
                                            <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                        @endif
                                    </div>
                                </div>
                                <div class="author-price d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="author-wrap d-flex align-items-center">
                                        <img src="{{ course_instructor_image($row->id) }}" alt="">
                                        <p class="name">{{ course_by_instructor($row->id)->name }}</p>
                                    </div>
                                    @if (isset($row->is_paid) && $row->is_paid == 0)
                                        <h4 class="theme-text-color title-5 fs-20px lh-26px fw-600">{{ get_phrase('Free') }}</h4>
                                    @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                        <h4 class="theme-text-color title-5 fs-20px lh-26px fw-600">
                                            {{ currency($row->discounted_price, 2) }}
                                        </h4>
                                        {{-- <del class="fs-14px fw-400">{{ currency($row->price, 2) }}</del> --}}
                                    @else
                                        <h4 class="theme-text-color title-5 fs-20px lh-26px fw-600">{{ currency($row->price, 2) }}</h4>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
