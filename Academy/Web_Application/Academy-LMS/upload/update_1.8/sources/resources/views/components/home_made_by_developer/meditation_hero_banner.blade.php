{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @php
                    $bannerData = json_decode(get_frontend_settings('banner_image'));
                    $banneractive = get_frontend_settings('home_page');

                    if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
                        $banner = json_decode(get_frontend_settings('banner_image'))->$banneractive;
                    }
                @endphp
                <div class="maditation-banner-typography">
                    <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/meditation-typophy.svg') }}" alt="">
                </div>
                <div class="maditation-banner-content mb-80 d-flex">
                    <div class="maditation-banner-left">
                        <p class="info builder-editable" builder-identity="2">{{ get_frontend_settings('banner_sub_title') }}</p>
                        <a href="{{ route('courses') }}" class="explore-btn1">
                            <span class="text builder-editable" builder-identity="3">{{ get_phrase('Explore Courses') }}</span>
                            <span class="icon">
                                <img src="{{ asset('assets/frontend/default/image/arrow-send-white.svg') }}" alt="">
                            </span>
                        </a>
                    </div>
                    <div class="maditation-banner-image">
                        @if (isset($banner))
                            <img class="builder-editable" builder-identity="4" src="{{ asset($banner) }}" alt="">
                        @else
                            <img class="builder-editable" builder-identity="5" src="{{ asset('assets/frontend/default/image/maditation-banner.svg') }}" alt="">
                        @endif
                    </div>
                    <div class="maditation-banner-right">
                        <ul class="maditation-video-profiles d-flex align-items-center">
                            @php
                                $students = DB::table('users')->where('role', 'student')->take(4)->get();
                                $total_student = DB::table('users')->where('role', 'student')->get();
                                $free_courses = DB::table('courses')->where('is_paid', 0)->get();
                            @endphp
                            @foreach ($students as $student)
                                <li>
                                    <img src="{{ get_image($student->photo) }}" alt="">
                                </li>
                            @endforeach
                        </ul>
                        <div class="maditation-class-participant mb-20 d-flex flex-wrap">
                            <div class="class-participant">
                                <h2 class="total fw-500">{{ count($total_student) }}+</h2>
                                <p class="info builder-editable" builder-identity="6">{{ get_phrase('Participant') }}</p>
                            </div>
                            <div class="class-participant">
                                <h2 class="total fw-500">{{ count($free_courses) }}+</h2>
                                <p class="info builder-editable" builder-identity="7">{{ get_phrase('Online Free Courses') }}</p>
                            </div>
                        </div>
                        <div class="maditation-beginner-lesson">
                            <h2 class="total fw-500">{{ get_phrase('10%') }}</h2>
                            <p class="info builder-editable" builder-identity="8">{{ get_phrase('Lessons for beginner') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
