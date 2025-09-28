{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="home1-banner-section">
    <div class="container">
        <div class="row mb-80 align-items-center">
            <div class="col-md-6 order-2 order-md-1">
                <div class="home1-banner-content">
                    @php
                        $banner_title = get_frontend_settings('banner_title');
                        $arr = explode(' ', $banner_title);
                        $phrase_two = end($arr);
                        $phrase_one = str_replace($phrase_two, '', $banner_title);
                    @endphp
                    <p class="light builder-editable" builder-identity="1">{{ get_phrase('The Leader in online learning') }}</p>
                    <h1 class="title">
                        <span class="builder-editable" builder-identity="2">{{ $phrase_one }}</span>
                        <span class="highlight builder-editable" builder-identity="3">{{ $phrase_two }}</span>
                    </h1>
                    <p class="info mb-20 builder-editable" builder-identity="4">{{ get_frontend_settings('banner_sub_title') }}</p>
                    <div class="buttons d-flex align-items-center flex-wrap">
                        <a href="{{ route('courses') }}" class="theme-btn1 text-center builder-editable" builder-identity="5">{{ get_phrase('Get Started') }}</a>
                        <a data-bs-toggle="modal" data-bs-target="#promoVideo" href="#" class="border-btn1 builder-editable" builder-identity="6">
                            {{ get_phrase('Learn More') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 order-1 order-md-2">
                <div class="home1-banner-image">
                    @php
                        $bannerData = json_decode(get_frontend_settings('banner_image'));
                        $banneractive = get_frontend_settings('home_page');

                        if ($bannerData !== null && is_object($bannerData) && property_exists($bannerData, $banneractive)) {
                            $banner = json_decode(get_frontend_settings('banner_image'))->$banneractive;
                        }

                    @endphp
                    @if (isset($banner))
                        <img class="builder-editable" builder-identity="7" src="{{ asset($banner) }}" alt="">
                    @else
                        <img class="builder-editable" builder-identity="8" src="{{ asset('assets/frontend/default/image/home1-banner.webp') }}" alt="">
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

    function scrollToSmoothly(pos, time) {
        if (isNaN(pos)) {
            throw "Position must be a number";
        }
        if (pos < 0) {
            throw "Position can not be negative";
        }
        var currentPos = window.scrollY || window.screenTop;
        if (currentPos < pos) {
            var t = 10;
            for (let i = currentPos; i <= pos; i += 10) {
                t += 10;
                setTimeout(function() {
                    window.scrollTo(0, i);
                }, t / 2);
            }
        } else {
            time = time || 2;
            var i = currentPos;
            var x;
            x = setInterval(function() {
                window.scrollTo(0, i);
                i -= 10;
                if (i <= pos) {
                    clearInterval(x);
                }
            }, time);
        }
    }
</script>
