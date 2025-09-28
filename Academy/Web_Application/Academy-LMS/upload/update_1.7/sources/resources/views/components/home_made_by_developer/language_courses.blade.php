{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <!-- Section Title -->
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-1 fs-32px lh-36px text-center mb-30 builder-editable" builder-identity="1">{{ get_phrase('Special Featured Course.') }}</h1>
            </div>
        </div>
        <div class="row row-20 mb-100px">
            @php
                $featured_courses = DB::table('courses')->where('status', 'active')->latest('id')->get();
            @endphp

            @foreach ($featured_courses->take(4) as $key => $row)
                <div class="col-lg-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="d-block h-100 w-100 max-sm-350px">
                        <div class="lms-1-card">
                            <div class="lms-1-card-body">
                                <div class="d-flex gap-3 align-items-center flex-sm-row flex-column">
                                    <div class="list-view-banner1">
                                        <img src="{{ get_image($row->thumbnail) }}" alt="">
                                    </div>
                                    <div class="list-view-details1 w-100">
                                        <h5 class="title-1 fs-20px lh-28px fw-semibold mb-6px title-1 fs-20px lh-28px fw-semibold mb-6px ellipsis-line-2">{{ ucfirst($row->title) }}</h5>
                                        <div class="card-rating-reviews1 mb-20 d-flex align-items-center flex-wrap">
                                            @php
                                                $ratings = DB::table('reviews')->where('course_id', $row->id)->pluck('rating')->toArray();
                                                $average_rating = count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
                                                $full_stars = floor($average_rating);
                                                $has_half_star = $average_rating - $full_stars >= 0.5;
                                                $review_count = count($ratings);
                                            @endphp
                                            @if ($review_count > 0)
                                                <div class="d-flex align-items-center gap-1">
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
                                                <p class="subtitle-1 fs-14px lh-20px">({{ $review_count }} {{ get_phrase('Reviews') }})</p>
                                            @endif
                                        </div>
                                        <div class="card-programs-1 mb-20 d-flex align-items-center gap-2">
                                            <div class="card-flug-sm">
                                                <i class="fi-rr-book-open-cover"></i>
                                            </div>
                                            <p class="subtitle-1 fs-14px lh-20px">{{ lesson_count($row->id) }} {{ get_phrase('lesson') }}</p>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="card-author-sm">
                                                    <img src="{{ course_instructor_image($row->id) }}" alt="">
                                                </div>
                                                <div class="title-1 fs-14px fw-medium">{{ course_by_instructor($row->id)->name }}</div>
                                            </div>
                                            @if (isset($row->is_paid) && $row->is_paid == 0)
                                                <h4 class="title-1 fs-20px lh-28px fw-semibold">{{ get_phrase('Free') }}</h4>
                                            @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                                <h4 class="title-1 fs-20px lh-28px fw-semibold">
                                                    {{ currency($row->discounted_price, 2) }}
                                                    <del class="fs-14px text-secondary">{{ currency($row->price) }}</del>
                                                </h4>
                                            @else
                                                <h4 class="title-1 fs-20px lh-28px fw-semibold">{{ currency($row->price, 2) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
            <div class="col-lg-12">
                <div class="d-flex justify-content-center mt-5">
                    <a href="{{ route('courses') }}" class="btn btn-outline-primary-1 builder-editable" builder-identity="2">{{ get_phrase('View More') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
