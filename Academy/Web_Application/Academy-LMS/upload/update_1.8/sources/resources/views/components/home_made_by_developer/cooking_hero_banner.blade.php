{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}
@php
    $total_students = DB::table('users')->where('role', 'student')->get();
    $total_instructors = DB::table('users')->where('role', 'instructor')->get();
    $free_courses = DB::table('courses')->where('is_paid', 0)->get();
    $premium_courses = DB::table('courses')->where('is_paid', 1)->get();
@endphp
<!-- Banner Area Start -->
<section class="cooking-banner-section">
    <div class="container">
        <div class="row row-28">
            <div class="col-lg-6 order-2 order-lg-1">
                @php
                    $banner_title = get_frontend_settings('banner_title');
                    $arr = explode(' ', $banner_title);
                    $first_word = $arr[0];
                    $second_word = $arr[1] ?? '';
                    array_shift($arr);
                    array_shift($arr);
                    $remaining_text = implode(' ', $arr);
                @endphp
                <div class="cooking-banner-details">
                    <p class="light mb-0 builder-editable" builder-identity="1">{{ get_phrase('WELLCOME TO CHEF') }}</p>
                    <h1 class="title mb-20"><span class="highlight builder-editable" builder-identity="2">{{ $first_word }}</span> <span class="small builder-editable" builder-identity="3">{{ $second_word }} {{ $remaining_text }}</span></h1>
                    <p class="info mb-30 pe-5 builder-editable" builder-identity="4">{{ get_frontend_settings('banner_sub_title') }}</p>

                    <div class="d-flex align-items-center">
                        <a href="{{ route('courses') }}" class="rectangle-btn1 builder-editable" builder-identity="5">{{ get_phrase('Visit Courses') }}</a>
                        <a class="rectangle-btn2 ms-5 theme-text-color" data-bs-toggle="modal" data-bs-target="#promoVideo" href="#"><i class="ms-4 me-3 fa-solid fa-play"></i>
                            {{ get_phrase('Learn More') }}</a>
                    </div>

                    <ul class="cooking-banner-sponsors owl-carousel owl-theme cook_slider d-flex align-items-center flex-wrap">
                        <li>
                            <div class="item">
                                <h1>{{ count($total_students) }}+</h1>
                                <p class="builder-editable" builder-identity="6">{{ get_phrase('Enrolled Learners') }}</p>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <h1>{{ count($total_instructors) }}+</h1>
                                <p class="builder-editable" builder-identity="7">{{ get_phrase('Online Instructors') }}</p>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <h1>{{ count($premium_courses) }}+</h1>
                                <p class="builder-editable" builder-identity="8">{{ get_phrase('Premium courses') }}</p>
                            </div>
                        </li>
                        <li>
                            <div class="item">
                                <h1>{{ count($free_courses) }}+</h1>
                                <p class="builder-editable" builder-identity="9">{{ get_phrase('Cost-free course') }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2">
                <div class="cooking-banner-image">
                    @php
                        $bannerData = json_decode(get_frontend_settings('banner_image'));
                        $banneractive = get_frontend_settings('home_page');

                        if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
                            $banner = json_decode(get_frontend_settings('banner_image'))->$banneractive;
                        }

                    @endphp
                    @if (isset($banner))
                        <img class="builder-editable" builder-identity="10" src="{{ asset($banner) }}" alt="">
                    @else
                        <img class="builder-editable" builder-identity="11" src="{{ asset('assets/frontend/default/image/cooking-banner.png') }}" alt="">
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vertically centered modal -->
<div class="modal fade-in-effect" id="promoVideo" tabindex="-1" aria-labelledby="promoVideoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-body bg-dark">
                <link rel="stylesheet" href="{{ asset('assets/global/plyr/plyr.css') }}">

                @if (get_frontend_settings('promo_video_provider') == 'youtube')
                    <div class="plyr__video-embed" id="promoPlayer">
                        <iframe height="500" src="{{ get_frontend_settings('promo_video_link') }}?origin=https://plyr.io&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1" allowfullscreen allowtransparency allow="autoplay"></iframe>
                    </div>
                @elseif (get_frontend_settings('promo_video_provider') == 'vimeo')
                    <div class="plyr__video-embed" id="promoPlayer">
                        <iframe height="500" id="promoPlayer" src="https://player.vimeo.com/video/{{ get_frontend_settings('promo_video_link') }}?loop=false&amp;byline=false&amp;portrait=false&amp;title=false&amp;speed=true&amp;transparent=0&amp;gesture=media" allowfullscreen allowtransparency
                            allow="autoplay"></iframe>
                    </div>
                @else
                    <video id="promoPlayer" playsinline controls>
                        <source src="{{ get_frontend_settings('promo_video_link') }}" type="video/mp4">
                    </video>
                @endif

                <script src="{{ asset('assets/global/plyr/plyr.js') }}"></script>
                <script>
                    "use strict";
                    var promoPlayer = new Plyr('#promoPlayer');
                </script>

            </div>
        </div>
    </div>
</div>

<script>
    "use strict";
    const myModalEl = document.getElementById('promoVideo')
    myModalEl.addEventListener('hidden.bs.modal', event => {
        promoPlayer.pause();
        $('#promoVideo').toggleClass('in');
    });
    myModalEl.addEventListener('shown.bs.modal', event => {
        promoPlayer.play();
        $('#promoVideo').toggleClass('in');
    });


    //instructor promo video modal
    var becomeInstructorPlyr = new Plyr('#becomeInstructorPlyr');
    const instructorModalEl = document.getElementById('becomeInstructor')
    instructorModalEl.addEventListener('hidden.bs.modal', event => {
        becomeInstructorPlyr.pause();
        $('#becomeInstructor').toggleClass('in');
    });
    instructorModalEl.addEventListener('shown.bs.modal', event => {
        becomeInstructorPlyr.play();
        $('#becomeInstructor').toggleClass('in');
    });
</script>
