{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container mb-80">
        <!-- Section title -->
        <div class="row">
            <div class="col-md-12">
                <div class="home1-section-title">
                    <h1 class="title mb-20 fw-500 builder-editable" builder-identity="1">{{ get_phrase('Top Rated Course') }}</h1>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('Top rated course showcases the highest-rated course based on student reviews and performance metrics.') }}</p>
                </div>
            </div>
        </div>
        <!-- Courses -->
        <div class="row row-20 mb-30">
            @php
                $top_courses = DB::table('courses')
                    ->leftJoin('payment_histories', 'courses.id', '=', 'payment_histories.course_id')
                    ->select('courses.id', 'courses.slug', 'courses.thumbnail', 'courses.title', 'courses.average_rating', 'courses.discount_flag', 'courses.is_paid', 'courses.price', 'courses.discounted_price', DB::raw('COUNT(payment_histories.id) as total_sales'))
                    ->groupBy('courses.id', 'courses.slug', 'courses.thumbnail', 'courses.title', 'courses.average_rating', 'courses.discount_flag', 'courses.is_paid', 'courses.price', 'courses.discounted_price')
                    ->where('status', 'active')
                    ->orderByDesc('total_sales')
                    ->take(4) // Number of courses you want to get, e.g., top 4
                    ->get();
            @endphp
            @foreach ($top_courses as $key => $row)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="course-card1-link">
                        <div class="course-card1-inner">
                            <div class="banner">
                                <img src="{{ get_image($row->thumbnail) }}" alt="">
                            </div>
                            <div class="course-card1-details">
                                <div class="rating-reviews d-flex align-items-center flex-wrap">
                                    <div class="rating d-flex align-items-center">
                                        @php
                                            $rating = $row->average_rating;
                                            $full_stars = floor($rating);
                                            $has_half_star = $rating - $full_stars >= 0.5;
                                        @endphp

                                        @for ($i = 0; $i < 5; $i++)
                                            @if ($i < $full_stars)
                                                <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="Full Star">
                                            @elseif ($i == $full_stars && $has_half_star)
                                                <img src="{{ asset('assets/frontend/default/image/star-yellow-half-14.svg') }}" alt="Half Star">
                                            @else
                                                <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="...">
                                            @endif
                                        @endfor

                                    </div>
                                    <p class="reviews">({{ DB::table('reviews')->where('course_id', $row->id)->count() }} {{ get_phrase('Reviews') }})</p>
                                </div>
                                <div class="title-info">
                                    <h4 class="title ellipsis-line-2">{{ ucfirst($row->title) }}</h4>
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
        @if (count($top_courses) > 3)
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('courses') }}" class="border-btn1 builder-editable" builder-identity="3">{{ get_phrase('View More') }}</a>
                    </div>
                </div>
        @endif
    </div>
    </div>
</section>
