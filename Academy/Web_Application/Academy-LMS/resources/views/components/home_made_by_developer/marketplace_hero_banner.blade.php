{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<style>
    body,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    a,
    button {
        font-family: 'sora-regular' !important;
    }

    .btn-danger-1,
    .btn-whitelight,
    .info,
    input {
        font-family: 'sora-regular' !important;
    }

    .subtitle-1,
    .subtitle-2,
    .subtitle-3,
    .subtitle-4,
    .subtitle-5 {
        font-family: 'sora-semi-bold' !important;
        font-weight: 500;
    }

    .title,
    .title-1,
    .title-2,
    .title-3,
    .title-4,
    .title-5 {
        font-family: 'sora-bold' !important;
        font-weight: 700;
    }

    .form-control.sub1-form-control {
        padding: 9px 15px;
    }
</style>
@php
    $bannerData = json_decode(get_frontend_settings('banner_image'));
    $banneractive = get_frontend_settings('home_page');
    if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
        $banner = asset(json_decode(get_frontend_settings('banner_image'))->$banneractive);
    } else {
        $defaultBanner = asset('assets/frontend/default/image/bannerM.jpg');
    }
    $total_students = DB::table('users')->where('role', 'student')->get();
@endphp
<section>
    <div class="container">
        <div class="row mb-50px">
            <div class="col-md-12">
                <div class="lms-banner-area-3" style="background-image: url({{ isset($banner) ? $banner : $defaultBanner }});">
                    <!-- Swiper -->
                    <div class="swiper banner-swiper-1">
                        @php
                            $settings = get_homepage_settings('marketplace');
                            $marketplace_banner = json_decode($settings);
                            if ($marketplace_banner && isset($marketplace_banner->slider)) {
                                $sliders = $marketplace_banner->slider;
                            }
                        @endphp
                        @if ($marketplace_banner)
                            <div class="swiper-wrapper">
                                @foreach ($sliders as $key => $slider)
                                    <div class="swiper-slide">
                                        <div class="lms-banner-slide">
                                            <p class="text-white-highlight1 mb-12px">{{ $slider->banner_title }}</p>
                                            <h1 class="title-4 fs-56px lh-normal fw-bold text-white text-center mb-50px">{{ $slider->banner_description }}</h1>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-pagination"></div>
                        @endif
                        <a href="{{ route('courses') }}" class="btn btn-white1 slider_btn">
                            <span class="builder-editable" builder-identity="1">{{ get_phrase('Explore Courses') }}</span>
                            <span class="fi-rr-arrow-right"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
