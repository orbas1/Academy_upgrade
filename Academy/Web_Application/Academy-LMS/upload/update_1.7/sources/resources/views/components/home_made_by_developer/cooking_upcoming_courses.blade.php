{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="cooking-section-title">
                    <h3 class="title-5 fs-32px lh-42px fw-600 text-center mb-20 builder-editable" builder-identity="1">{{ get_phrase('Upcoming Courses') }}</h3>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('Highlights the latest courses set to launch, giving students a sneak peek at new opportunities for learning and skill development. Stay ahead with our curated selection of upcoming educational offerings!') }}</p>
                </div>
            </div>
        </div>

        <div class="row row-28 mb-110">
            @php
                $upcoming_courses = DB::table('courses')->where('status', 'upcoming')->latest('id')->take(4)->get();
            @endphp
            @foreach ($upcoming_courses as $key => $row)
                <div class="col-md-12">
                    <a href="{{ route('course.details', $row->slug) }}" class="cooking-course-list-link">
                        <div class="cooking-course-list-card d-flex align-items-center justify-content-between">
                            <div class="cooking-course-list-banner-title d-flex align-items-center">
                                <div class="banner">
                                    <img src="{{ get_image($row->thumbnail) }}" alt="">
                                </div>
                                <div class="title-wrap">
                                    <h5 class="title">{{ $row->title }}</h5>
                                    <div class="author-wrap d-flex align-items-center">
                                        <img src="{{ course_instructor_image($row->id) }}" alt="">
                                        <p class="name">{{ course_by_instructor($row->id)->name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="cooking-course-list-other d-flex">
                                <div class="date-time">
                                    <div>
                                        <p class="info">{{ get_phrase('Lesson') }}</p>
                                        <p class="value">{{ lesson_count($row->id) }}</p>
                                    </div>
                                </div>
                                <div class="date-time">
                                    <div>
                                        <p class="info">{{ get_phrase('Duration') }}</p>
                                        <p class="value">{{ total_durations('duration') }}</p>
                                    </div>
                                </div>
                                <div class="date-time-price">
                                    <div>
                                        <p class="info">{{ get_phrase('Price') }}</p>
                                        @if (isset($row->discount_flag) && $row->discount_flag == 1)
                                            <p class="value">{{ currency($row->price - $row->discounted_price, 2) }}</p>
                                        @elseif (isset($row->is_paid) && $row->is_paid == 0)
                                            <p class="value">{{ get_phrase('Free') }}</p>
                                        @else
                                            <p class="value">{{ currency($row->price, 2) }}</p>
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
