{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container mb-80">
        <!-- Section title -->
        <div class="row">
            <div class="col-md-12">
                <div class="home1-section-title">
                    <h1 class="title mb-20 fw-500 builder-editable" builder-identity="1">{{ get_phrase('Special Featured Course') }}</h1>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('Those course highlights a handpicked course with exceptional content or exclusive offerings.') }}</p>
                </div>
            </div>
        </div>
        <!-- Courses -->
        <div class="row row-20 mb-30">
            @php
                $featured_courses = DB::table('courses')->where('status', 'active')->latest('id')->get();
            @endphp
            @foreach ($featured_courses->take(4) as $key => $row)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="course-card1-link">
                        <div class="course-card1-inner">
                            <div class="banner">
                                <img src="{{ get_image($row->thumbnail) }}" alt="">
                            </div>
                            <div class="course-card1-details">
                                <div class="rating-reviews d-flex align-items-center flex-wrap">
                                    @php
                                        $ratings = DB::table('reviews')->where('course_id', $row->id)->pluck('rating')->toArray();
                                        $average_rating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
                                        $full_stars = floor($average_rating);
                                        $has_half_star = $average_rating - $full_stars >= 0.5;
                                        $review_count = count($ratings);
                                    @endphp

                                    @if ($review_count > 0)
                                        <div class="rating d-flex align-items-center">
                                            @for ($i = 0; $i < 5; $i++)
                                                @if ($i < $full_stars)
                                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="Full Star">
                                                @elseif ($i == $full_stars && $has_half_star)
                                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-half-14.svg') }}" alt="Half Star">
                                                @else
                                                    <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="Empty Star">
                                                @endif
                                            @endfor
                                        </div>
                                        <p class="reviews">({{ $review_count }} {{ get_phrase('Reviews') }})</p>
                                    @endif
                                </div>
                                <div class="title-info">
                                    <h4 class="title">{{ ucfirst($row->title) }}</h4>
                                    <p class="info">{{ $row->short_description ?? '' }}</p>
                                </div>
                                <div class="course-card1-leasons-students d-flex align-items-center flex-wrap">
                                    <div class="leasons-students d-flex align-items-center">
                                        <img src="{{ asset('assets/frontend/default/image/book-open-16.svg') }}" alt="">
                                        <p class="total fw-500">{{ lesson_count($row->id) }} {{ get_phrase('lesson') }}</p>
                                    </div>
                                    <div class="leasons-students d-flex align-items-center">
                                        <img src="{{ asset('assets/frontend/default/image/user-square.svg') }}" alt="">
                                        <p class="total fw-500">{{ course_enrollments($row->id) }} {{ get_phrase('Students') }}</p>
                                    </div>
                                </div>
                                <div class="course-card1-author-price d-flex align-items-end justify-content-between">
                                    <div class="author d-flex align-items-center">
                                        <div class="profile">
                                            <img src="{{ course_instructor_image($row->id) }}" alt="">
                                        </div>
                                        <p class="name">{{ course_by_instructor($row->id)->name }}</p>
                                    </div>
                                    <div class="prices">
                                        @if (isset($row->is_paid) && $row->is_paid == 0)
                                            <p class="new-price">{{ get_phrase('Free') }}</p>
                                        @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                            <p class="old-price">{{ currency($row->price, 2) }}</p>
                                            <p class="new-price">{{ currency($row->discounted_price, 2) }}</p>
                                        @else
                                            <p class="new-price">{{ currency($row->price, 2) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>


        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-center">
                    <a href="{{ route('courses') }}" class="border-btn1 builder-editable" builder-identity="3">{{ get_phrase('View More') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
