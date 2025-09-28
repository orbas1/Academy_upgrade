{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-1 fs-32px lh-36px text-center mb-30 builder-editable" builder-identity="1">{{ get_phrase('Meet Our Team') }}</h1>
            </div>
        </div>
        <div class="row g-20px mb-100px">
            @php

                $popular_instaructors = DB::table('courses')->select('enrollments.user_id', DB::raw('count(*) as enrol_number'))->join('enrollments', 'courses.id', '=', 'enrollments.course_id')->groupBy('enrollments.user_id')->orderBy('enrollments.user_id', 'DESC')->limit(10)->get();
            @endphp
            @foreach ($popular_instaructors as $key => $instructor)
                @php
                    $instructorDetails = App\Models\User::where('id', $instructor->user_id)->first();
                    if (!$instructorDetails) {
                        continue;
                    }
                @endphp
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="lms-1-card max-sm-350px">
                        <div class="lms-1-card-body p-12px">
                            <div class="grid-view-banner1 mb-12px">
                                <a href="{{ route('instructor.details', ['name' => slugify($instructorDetails->name), 'id' => $instructorDetails->id]) }}">
                                    <img src="{{ get_image($instructorDetails->photo) }}" alt="">
                                </a>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between gap-1">
                                    <a href="{{ route('instructor.details', ['name' => slugify($instructorDetails->name), 'id' => $instructorDetails->id]) }}">
                                        <h5 class="title-1 fs-16px lh-24px fw-semibold mb-1">{{ $instructorDetails->name }}</h5>
                                        <p class="subtitle-1 fs-14px lh-20px">{{ get_phrase('Instructor') }}</p>
                                    </a>
                                    <div class="d-flex gap-1">
                                        <a href="{{ $instructorDetails->facebook }}" class="social-link-1">
                                            <img src="{{ asset('assets/frontend/default/image/lg-facebook.svg ') }}" alt="">
                                        </a>
                                        <a href="{{ $instructorDetails->twitter }}" class="social-link-1">
                                            <img src="{{ asset('assets/frontend/default/image/lg-twiter.svg') }}" alt="">
                                        </a>
                                        <a href="{{ $instructorDetails->linkedin }}" class="social-link-1">
                                            <i class="fa-brands fa-linkedin"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
