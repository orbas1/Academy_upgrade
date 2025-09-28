{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@php
    $bannerData = json_decode(get_frontend_settings('banner_image'));
    $banneractive = get_frontend_settings('home_page');
    if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
        $banner = asset(json_decode(get_frontend_settings('banner_image'))->$banneractive);
    } else {
        $defaultBanner = asset('assets/frontend/default/image/development-banner1.webp');
    }
    $total_students = DB::table('users')->where('role', 'student')->get();
@endphp
<section class="dev-banner-section" style="background-image: url({{ isset($banner) ? $banner : $defaultBanner }});">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="development-banner-area">
                    @php
                        $banner_title = get_frontend_settings('banner_title');
                        $arr = explode(' ', $banner_title);
                        $phrase_two = end($arr);
                        $phrase_one = str_replace($phrase_two, '', $banner_title);
                    @endphp
                    <h1 class="title">
                        <span class="builder-editable" builder-identity="1">{{ $phrase_one }}</span>
                        <span class=" font-italic highlight builder-editable" builder-identity="2">{{ $phrase_two }}</span>
                    </h1>
                    <a href="javascript:void(0);" class="video-play-btn" type="button" data-bs-toggle="modal" data-bs-target="#promoVideo">
                        <span class="icon">
                            <img src="{{ asset('assets/frontend/default/image/play-black-large.svg') }}" alt="">
                        </span>
                        <span>{{ get_phrase('Watch Video') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Banner Area End -->

<!-- Hero Area Start -->
<section class="development-hero-section1 mb-80">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="development-hero-area1 d-flex align-items-center justify-content-between">
                    <div class="hero-rated-profile-area">
                        <ul class="profiles d-flex align-items-center">

                            @php
                                $students = DB::table('users')->where('role', 'student')->take(2)->latest('id')->get();
                            @endphp
                            @foreach ($students as $student)
                                <li>
                                    <img data-bs-toggle="tooltip" title="{{ $student->name }}" src="{{ get_image($student->photo) }}" alt="">
                                </li>
                            @endforeach
                            <li>
                                <img src="{{ asset('assets/frontend/default/image/arrow-rotate-white-16.svg') }}" alt="">
                            </li>
                        </ul>
                        <p class="info builder-editable" builder-identity="3">{{ count($total_students) }}+ {{ get_phrase('User already register and signing up for using it') }}</p>
                    </div>
                    <p class="hero-info builder-editable" builder-identity="4">{{ get_frontend_settings('banner_sub_title') }}</p>
                    <a href="{{ route('courses') }}" class="btn-black1 builder-editable" builder-identity="5">{{ get_phrase('Get Courses') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Hero Area End -->



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
</script>
