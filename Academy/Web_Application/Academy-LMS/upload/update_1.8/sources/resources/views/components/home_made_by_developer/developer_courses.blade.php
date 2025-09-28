{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <!-- Section Title -->
        <div class="row">
            <div class="col-md-12">
                <div class="dev-section-title">
                    <h1 class="title mb-20">
                        <span class="builder-editable" builder-identity="1">{{ get_phrase('Pick A Course To') }}</span>
                        <span class="highlight builder-editable" builder-identity="2">{{ get_phrase('Get Started') }}</span>
                    </h1>
                    <p class="info builder-editable" builder-identity="3">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
        </div>
        <div class="row row-20 mb-110">
            @php
                $featured_courses = DB::table('courses')->where('status', 'active')->latest('id')->get();
            @endphp
            @foreach ($featured_courses->take(4) as $key => $row)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="dev-course-card-link">
                        <div class="dev-course-card">
                            <div class="banner">
                                <img src="{{ get_image($row->thumbnail) }}" alt="banner">
                            </div>
                            @php
                                $ratings = DB::table('reviews')->where('course_id', $row->id)->pluck('rating')->toArray();
                                $average_rating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
                                $full_stars = floor($average_rating);
                                $has_half_star = $average_rating - $full_stars >= 0.5;
                                $review_count = count($ratings);
                            @endphp
                            <div class="dev-course-card-body">
                                <h5 class="title ellipsis-line-2">{{ ucfirst($row->title) }}</h5>
                                <div class="reviews d-flex align-items-center">
                                    @if ($review_count > 0)
                                        <div class="ratings d-flex align-items-center">
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
                                        <p class="total fw-500">{{ $review_count }} {{ get_phrase('Reviews') }}</p>
                                    @endif
                                </div>

                                @if (isset($row->is_paid) && $row->is_paid == 0)
                                    <p class="price">{{ get_phrase('Free') }}</p>
                                @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                    <p class="price">
                                        {{ currency($row->discounted_price, 2) }}
                                        <del class="fs-14px text-secondary">{{ currency($row->price) }}</del>
                                    </p>
                                @else
                                    <p class="price">{{ currency($row->price, 2) }}</p>
                                @endif

                                <div class="leason-student d-flex align-items-center">
                                    <div class="leasons-students d-flex align-items-center">
                                        <i class="fi-rr-book-open-cover"></i>
                                        <p class="total fw-500">{{ lesson_count($row->id) }}{{ get_phrase('lessons') }}</p>
                                    </div>
                                    <div class="leasons-students d-flex align-items-center">
                                        <i class="fi-rr-users"></i>
                                        <p class="total fw-500">{{ course_enrollments($row->id) }} {{ get_phrase('Students') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
            @if (count($featured_courses) > 4)
                <!-- Button  -->
                <div class="col-xl-12">
                    <div class="dev-course-btn-area d-flex justify-content-center">
                        <a href="{{ route('courses') }}" class="btn-black-arrow1">
                            <span>{{ get_phrase('View More') }}</span>
                            <i class="fi-rr-angle-small-right"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
