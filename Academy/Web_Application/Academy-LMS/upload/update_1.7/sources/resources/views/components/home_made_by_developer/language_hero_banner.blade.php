{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@php
    $total_students = DB::table('users')->where('role', 'student')->get();
    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
@endphp
<!-- Banner Area Start -->
<section>
    <div class="container">
        <div class="lms-banner-wrap-1">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-6">
                    @php
                        $banner_title = get_frontend_settings('banner_title');
                        $arr = explode(' ', $banner_title);
                        $first_word = $arr[0];
                        $second_word = $arr[1] ?? '';
                        array_shift($arr);
                        array_shift($arr);
                        $remaining_text = implode(' ', $arr);
                    @endphp
                    <div class="lms-banner-content-1">
                        <p class="text-bordered-1 mb-6px builder-editable" builder-identity="1">{{ get_phrase('Education For Eeveryone') }}</p>
                        <h1 class="title-1 fs-44px lh-60px mb-14px fw-600">
                            <span class="builder-editable" builder-identity="2">{{ $first_word }}</span>
                            <span class="italic-1 fw-semibold builder-editable" builder-identity="3">{{ $second_word }}</span>
                            <span class="builder-editable" builder-identity="4">{{ $remaining_text }}</span>
                        </h1>
                        <p class="subtitle-1 fs-16px lh-24px mb-3 builder-editable" builder-identity="5">{{ get_frontend_settings('banner_sub_title') }}</p>
                        <form action="{{ route('courses') }}" method="get">
                            <div class="lms-subscribe-form-1 d-flex gap-2 align-items-center flex-wrap">
                                <input type="text" name="search" class="form-control form-control-1" placeholder="{{ get_phrase('Search here') }}" @if (request()->has('search')) value="{{ request()->input('search') }}" @endif>
                                <button type="submit" class="btn btn-primary-1">{{ get_phrase('Search') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xl-7 col-lg-6">
                    <div class="lms-banner-area-1 d-flex flex-sm-row flex-column">
                        <div class="lms-banner-1">
                            @php
                                $bannerData = json_decode(get_frontend_settings('banner_image'));
                                $banneractive = get_frontend_settings('home_page');

                                if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
                                    $banner = json_decode(get_frontend_settings('banner_image'))->$banneractive;
                                }

                            @endphp
                            @if (isset($banner))
                                <img class="builder-editable" builder-identity="6" src="{{ asset($banner) }}" alt="">
                            @else
                                <img class="builder-editable" builder-identity="7" src="{{ asset('assets/frontend/default/image/language-banner.webp') }}" alt="">
                            @endif
                        </div>
                        <div class="mt-auto mb-30px lms-banner-items1">
                            <div class="mb-40px">
                                <h2 class="title-1 fs-44px lh-60px fw-semibold mb-1">{{ count($total_students) }}+</h2>
                                <p class="subtitle-1 fs-16px lh-24px builder-editable" builder-identity="8">{{ get_phrase('User already register and signing up for using it') }}</p>
                            </div>
                            <div>
                                <h2 class="title-1 fs-44px lh-60px fw-semibold mb-1">{{ count($total_instructors) }}+</h2>
                                <p class="subtitle-1 fs-16px lh-24px builder-editable" builder-identity="9">{{ get_phrase('Online Instructor have a new ideas every week.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
