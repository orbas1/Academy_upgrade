{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title-2 mb-50px">
                    <h1 class="title-5 fs-32px lh-42px fw-500 mb-20px text-center builder-editable" builder-identity="1">{{ get_phrase('Our Online Courses') }}</h1>
                    <p class="subtitle-5 fs-15px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="row g-28px mb-50px">
            @php
                $featured_courses = DB::table('courses')->where('status', 'active')->latest('id')->take(4)->get();
            @endphp
            @foreach ($featured_courses->take(4) as $key => $row)
                @php
                    $ratings = DB::table('reviews')->where('course_id', $row->id)->pluck('rating')->toArray();
                    $average_rating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : null;
                @endphp
                <div class="col-lg-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="d-block h-100 w-100 max-sm-350px">
                        <div class="lms-1-card">
                            <div class="lms-1-card-body">
                                <div class="d-flex gap-3 align-items-center flex-sm-row flex-column">
                                    <div class="list-view-banner2">
                                        <img src="{{ get_image($row->thumbnail) }}" alt="banner">
                                    </div>
                                    <div class="list-view-details1 w-100">
                                        <h5 class="title-5 fs-18px lh-26px fw-500 mb-12px">{{ ucfirst($row->title) }}</h5>
                                        <p class="subtitle-5 fs-14px lh-24px mb-10px">{{ Str::limit(strip_tags($row->short_description), 78) }}</p>
                                        <div class="card-leason-rating2 d-flex gap-2 align-items-center justify-content-between">
                                            <div class="card-icon-text4 d-flex gap-2 align-items-center">
                                                <span class="fi-rr-book-open-cover"></span>
                                                <p class="subtitle-5 fs-13px lh-26px">{{ lesson_count($row->id) }} {{ get_phrase('lesson') }}</p>
                                            </div>
                                            <div class="card-rating3 d-flex gap-1 align-items-center">
                                                @if ($average_rating !== null)
                                                    <p class="title-5 fs-15px lh-26px fw-medium">{{ number_format($average_rating, 1) }}</p>
                                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-12px">
                                                <div class="card-author-sm">
                                                    <img src="{{ course_instructor_image($row->id) }}" alt="">
                                                </div>
                                                <div class="title-5 fs-13px lh-26px fw-medium">{{ course_by_instructor($row->id)->name }}</div>
                                            </div>


                                            @if (isset($row->is_paid) && $row->is_paid == 0)
                                                <h4 class="title-5 fs-20px lh-26px fw-500 text-danger-2">{{ get_phrase('Free') }}</h4>
                                            @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                                <h4 class="title-5 fs-20px lh-26px fw-500 text-danger-2">
                                                    {{ currency($row->discounted_price, 2) }}
                                                    <del class="fs-14px text-secondary">{{ currency($row->price) }}</del>
                                                </h4>
                                            @else
                                                <h4 class="title-5 fs-20px lh-26px fw-500 text-danger-2">{{ currency($row->price, 2) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="row mb-100px">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-center">
                    <a href="{{ route('courses') }}" class="btn btn-danger-1 builder-editable" builder-identity="3">{{ get_phrase('See More') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
