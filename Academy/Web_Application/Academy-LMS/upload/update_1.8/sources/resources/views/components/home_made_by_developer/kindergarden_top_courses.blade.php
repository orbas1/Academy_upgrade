{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="section-title-1 mb-50px">
                    <h1 class="title-3 mb-20px fs-40px lh-52px fw-medium text-center builder-editable" builder-identity="1">{{ get_phrase('Top Courses') }}</h1>
                    <p class="subtitle-2 fs-16px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="row g-28px mb-100px">
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
                    <a href="{{ route('course.details', $row->slug) }}" class="d-block h-100 w-100 max-sm-350px">
                        <div class="lms-1-card rounded-4 rounded-4 lms-card-hover1">
                            <div class="lms-1-card-body">
                                <div class="grid-view-banner1 mb-20px">
                                    <img class="h-190px" src="{{ get_image($row->thumbnail) }}" alt="banner">
                                </div>
                                <div class="course-card1-details">
                                    <h5 class="title-3 fs-18px lh-26px fw-medium mb-10px ellipsis-line-2">{{ ucfirst($row->title) }}</h5>
                                    <div class="card-leason-rating1 d-flex gap-2 align-items-center justify-content-between">
                                        <div class="card-icon-text1 d-flex gap-2 align-items-center">
                                            <span class="fi-rr-book-open-cover"></span>
                                            <p class="info">{{ lesson_count($row->id) }} {{ get_phrase('lessons') }}</p>
                                        </div>
                                        <div class="card-rating1 d-flex gap-1 align-items-center">
                                            <p class="rating">{{ number_format($row->average_rating, 1) }}</p>
                                            <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                        </div>
                                    </div>
                                    <div class="kg-card-profile-price d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="card-author-sm">
                                                <img src="{{ course_instructor_image($row->id) }}" alt="">
                                            </div>
                                            <div class="title-1 fs-13px lh-26px fw-bold">{{ course_by_instructor($row->id)->name }}</div>
                                        </div>

                                        @if (isset($row->is_paid) && $row->is_paid == 0)
                                            <h4 class="price title-1 fs-20px lh-26px fw-bold">{{ get_phrase('Free') }}</h4>
                                        @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                            <h4 class="price title-1 fs-18px lh-24px fw-bold">
                                                {{ currency($row->discounted_price, 2) }}
                                            </h4>
                                            {{-- <del class="fs-14px fw-400">{{ currency($row->price, 2) }}</del> --}}
                                        @else
                                            <h4 class="price title-1 fs-20px lh-26px fw-bold">{{ currency($row->price, 2) }}</h4>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
