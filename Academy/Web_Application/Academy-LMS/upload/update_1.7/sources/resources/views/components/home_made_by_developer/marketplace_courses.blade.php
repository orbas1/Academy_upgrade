{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-4 fs-34px lh-44px fw-semibold mb-50px builder-editable" builder-identity="1">{{ get_phrase('Top Courses') }}</h1>
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
                        <div class="lms-1-card rounded-4 lms-card-hover2">
                            <div class="lms-1-card-body p-0">
                                <div class="grid-view-banner2 position-relative">
                                    <img class="h-190px radious-0" src="{{ get_image($row->thumbnail) }}" alt="">
                                </div>
                                <div class="p-4">
                                    <div class="mb-6px d-flex gap-2 align-items-center justify-content-between">
                                        <div class="card-icon-text2 d-flex gap-2 align-items-center">
                                            <span class="fi-rr-book-open-cover"></span>
                                            <p class="subtitle-4 fs-13px lh-26px">{{ lesson_count($row->id) }} {{ get_phrase('lessons') }}</p>
                                        </div>
                                        <div class="card-rating2 d-flex gap-1 align-items-center">
                                            @if ($row->average_rating)
                                                <p class="rating">{{ number_format($row->average_rating, 1) }}</p>
                                                <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                            @endif
                                        </div>
                                    </div>
                                    <h5 class="title-4 fs-18px lh-26px fw-semibold my-4 ellipsis-line-2">{{ ucfirst($row->title) }}</h5>
                                    <div class="d-flex align-items-center gap-2 justify-content-between flex-wrap mb-2">
                                        <div class="mk-card-price d-flex align-items-end gap-1 ">
                                            @if (isset($row->is_paid) && $row->is_paid == 0)
                                                <p class="title-4 fs-20px lh-26px fw-bold">{{ get_phrase('Free') }}</p>
                                            @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                                <p class="title-4 fs-20px lh-26px fw-bold">{{ currency($row->discounted_price, 2) }}
                                                </p>
                                                <p class="mk-old-price text-12px">{{ currency($row->price, 2) }}</p>
                                            @else
                                                <p class="title-4 fs-20px lh-26px fw-bold">{{ currency($row->price, 2) }}</p>
                                            @endif
                                        </div>
                                        <p class="btn btn-dark-1 px-3">{{ get_phrase('Learn More') }}</p>
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
