{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="mb-80">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="posesjourney-title-area d-flex align-items-start justify-content-between flex-wrap">
                    <h1 class="title builder-editable" builder-identity="1">{{ get_phrase('Top Course') }}</h1>
                    <a href="{{ route('courses') }}" class="explore-btn1">
                        <span class="text builder-editable" builder-identity="2">{{ get_phrase('See All Courses') }}</span>
                        <span class="icon">
                            <img src="{{ asset('assets/frontend/default/image/arrow-send-white.svg') }}" alt="">
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row row-30">
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
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('course.details', $row->slug) }}" class="pose-journey-link">
                        <div class="single-posesjourney-yoga">
                            <div class="banner">
                                <img src="{{ get_image($row->thumbnail) }}" alt="">
                            </div>
                            @if (isset($row->is_paid) && $row->is_paid == 0)
                                <p class="price">{{ get_phrase('Free') }}</p>
                            @elseif (isset($row->discount_flag) && $row->discount_flag == 1)
                                <p class="price">
                                    {{ currency($row->discounted_price, 2) }}
                                    <del class="fs-12px text-12px text-light">{{ currency($row->price) }}</del>
                                </p>
                            @else
                                <p class="price">{{ currency($row->price, 2) }}</p>
                            @endif
                            @php
                                $backgroundImage = asset('assets/frontend/default/image/pose-journey-shadow' . (($key % 3) + 1) . '.svg');
                            @endphp
                            <div class="overlay" style="--bgShape: url('{{ $backgroundImage }}')">
                                <div class="posejourney-overley d-flex justify-content-between align-items-center">
                                    <div class="title-area mb-5">
                                        <h5 class="title">{{ ucfirst($row->title) }}</h5>
                                        <p class="info">{{ $row->level ?? '' }}</p>
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
