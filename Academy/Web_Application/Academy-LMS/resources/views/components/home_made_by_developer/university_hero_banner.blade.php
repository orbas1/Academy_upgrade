{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}
<link rel="stylesheet" href="{{ asset('assets/frontend/default/fonts/urbanist/font.css') }}">
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
        font-family: 'urbanist', sans-serif !important;
    }

    .btn-danger-1,
    .btn-whitelight,
    .info {
        font-family: 'urbanist', sans-serif !important;
    }

    .subtitle-1,
    .subtitle-2,
    .subtitle-3,
    .subtitle-4,
    .subtitle-5 {
        font-family: 'urbanist', sans-serif !important;
        font-weight: 500;
    }

    .title,
    .title-1,
    .title-2,
    .title-3,
    .title-4,
    .title-5 {
        font-family: 'urbanist', sans-serif !important;
        font-weight: 700;
    }

    .scale-single-slide {
        display: flex;
        flex-wrap: nowrap;
        align-content: center;
        justify-content: center;
        align-items: center;
        aspect-ratio: 789/465;
    }


    /* Start sub header */
    .sub-header {
        background-color: #121421;
        border-color: #645b5b;
    }

    .sub-header .sub-header-left a {
        color: #fff !important;
    }

    .sub-header .nice-select.form-select {
        filter: invert(1);
        background-color: #edebdd;
    }

    /* End sub header */


    .sub-header,
    .header-area {
        --font-family: "Poppins", sans-serif;
        --bg-white: #000;
        --text-color: #9e9e9e;
        --color-white: #000;
        --color-1: #fff;
        --color-2: #d5e0f3;
        --color-black: #fff;
        --box-shadow: rgba(76, 76, 109, 0.2) 12px 11px 34px 11px;
        --box-shadow-2: rgba(79, 79, 112, 0.2) 0px 7px 29px 0px;
    }

    .offcanvas.offcanvas-start {
        --font-family: "Poppins", sans-serif;
        --bg-white: #fff;
        --text-color: #6b7385;
        --color-white: #fff;
        --color-1: #2f57ef;
        --color-2: #192335;
        --color-black: #000;
        --box-shadow: rgba(100, 100, 111, 0.2) 12px 11px 34px 11px;
        --box-shadow-2: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
    }

    /* Start main header */
    .header-area {
        background-color: #121421;
    }

    .header-area .header-light-logo {
        display: block !important;
    }

    .header-area .header-dark-logo {
        display: none !important;
    }

    .header-area .primary-menu .have-mega-menu .menu-parent-a::after {
        background: #000;
    }

    .header-area .primary-menu .have-mega-menu .menu-parent-a.active::after,
    .primary-menu .have-mega-menu .menu-parent-a:hover::after {
        border: 6px solid #ffffff !important;
        border-right: 6px solid #000000 !important;
        border-bottom: 0 !important;
        border-left: 6px solid #000000 !important;
        vertical-align: middle !important;
        border-radius: 3px !important;
        background: #fff !important;
        position: absolute !important;
        right: -18px !important;
        top: 9px !important;
        left: auto !important;
        width: 10px !important;
    }

    .header-area .form-control {
        background-color: #121421;
        border-color: #645b5b;
    }

    .header-area .form-control:focus {
        background-color: #232323;
        color: #c7c7c7 !important;
        border-color: #807a7a;
    }

    .header-area .main-mega-menu {
        background: #000000;
        box-shadow: 0 12px 20px #a7a7a754;
        border: 1px solid #4c4c4c;
    }

    .header-area .mega_list li a:hover {
        background: #2a2a2a;
        color: #ffff;
    }

    .header-area .child_category_menu {
        background: #000;
    }

    .header-area .us-btn {
        background-color: #121421;
    }

    .header-area .Userprofile .dropmenu-end {
        background-color: #000000;
        box-shadow: 0 12px 20px #a7a7a754;
    }

    .header-area .Userprofile .dropmenu-end a:hover svg path,
    .Userprofile .dropmenu-end a:hover {
        background-color: #000;
        color: #c664ff;
        fill: #c664ff;
    }

    .header-area .figure_text {
        color: #fff;
    }

    .header-area .primary-end a path {
        stroke: var(--text-color);
    }

    .header-area .primary-end a path:hover,
    .primary-end a.active path {
        stroke: #fff;
    }

    .header-area .toggle-bar {
        color: #9e9e9e !important;
    }

    .header-area .gradient {
        box-shadow: none;
        border-left: 0;
    }

    .service-card-banner-2>img {
        height: 200px;
        object-fit: cover;
    }

    /* End main header */
</style>
<section class="lms-banner-section4">
    <div class="container">
        <div class="row mb-100px">
            <div class="col-md-12">
                <div class="lms-banner-area-4">
                    <div class="uv-banner-content">
                        <h1 class="title-5 max-w-850px fs-56px lh-normal fw-500 text-white text-center mb-20px">
                            <span class="highlight-title">{{ get_frontend_settings('banner_title') }}</span>
                        </h1>
                        <p class="subtitle-5 max-w-620px fs-15px lh-24px text-white text-center mb-30px builder-editable" builder-identity="2">{{ get_frontend_settings('banner_sub_title') }}</p>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('courses') }}" class="btn btn-danger-1 builder-editable" builder-identity="3">{{ get_phrase('Get Started Now') }}</a>
                        </div>
                    </div>
                    <!-- Swiper -->
                    <div class="swiper scale-slider scale-slider-main">
                        <div class="swiper-wrapper">
                            @php
                                $university = json_decode(get_homepage_settings('university'));
                                $slider_items = json_decode($university->slider_items ?? '{}', true) ?? [];
                            @endphp
                            @foreach ($slider_items as $key => $slider_items)
                                @php $ext = pathinfo($slider_items, PATHINFO_EXTENSION); @endphp
                                @if (file_exists(public_path($slider_items)))
                                    <div class="swiper-slide">
                                        <div class="scale-single-slide">
                                            <div class="scale-slide-image">
                                                <img src="{{ asset($slider_items) }}" alt="banner">
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="swiper-slide">
                                        <div class="scale-single-slide">
                                            <div class="lms-video">
                                                @if ($ext == 'mp4' || $ext == 'webm')
                                                    <video class="universityPlayer" id="player{{ $key }}" src="{{ $slider_items }}" controls></video>
                                                @else
                                                    <div class="plyr__video-embed universityPlayer" id="player{{ $key }}">
                                                        <iframe src="{{ $slider_items }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="swiper-button-next" onclick="playerPlayPause()"></div>
                        <div class="swiper-button-prev" onclick="playerPlayPause()"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <span class="uv-banner-shape1"></span>
    <span class="uv-banner-shape2"></span>
</section>

<script>
    if ($('.universityPlayer').length > 0) {
        // Select all video elements
        const players = Array.from(document.querySelectorAll('.universityPlayer'));
        // Initialize Plyr for each video element
        const plyrInstances = players.map(player => new Plyr(player));
        // Function to pause all players except the currently playing one
        function pauseOtherPlayers(currentPlayer) {
            plyrInstances.forEach(player => {
                if (player !== currentPlayer && !player.paused) {
                    player.pause();
                }
            });
        }
        // Add 'play' event listener to each player
        plyrInstances.forEach(player => {
            player.on('play', () => {
                pauseOtherPlayers(player);
            });
        });
    }

    function playerPlayPause() {
        setTimeout(function() {
            if ($('.swiper-slide-active .lms-video .plyr:not(.plyr--playing)').length > 0) {
                $('.swiper-slide-active .lms-video .plyr:not(.plyr--playing)').trigger('click');
            } else {
                $('.plyr--playing').trigger('click');
            }
        }, 600);
    }
</script>
