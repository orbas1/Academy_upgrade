@if ($class->enrolled_user && auth()->user()->id)
    <!doctype html>
    <html lang="en">

    <head>
        {{ config(['app.name' => get_settings('system_title')]) }}
        <title>{{ $class->title }} | {{ config('app.name') }}</title>
        <!-- Required meta tags -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="{{ asset(get_frontend_settings('favicon')) }}" />
        <link rel="stylesheet" href="{{ asset('assets/frontend/default/css/bootstrap.min.css') }}">
        <script src="{{ asset('assets/frontend/default/js/jquery-3.7.1.min.js') }}"></script>
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/plyr/plyr.css') }}" />
        <script src="{{ asset('assets/global/plyr/plyr.js') }}"></script>

        <style>
            html {
                background-color: #212529;
            }

            .back-btn {
                position: absolute;
                top: 30px;
                left: 20px;
                z-index: 999;
                border-radius: 50px;
                background: rgba(127, 127, 127, 0.38);
                text-decoration: none;
                width: 45px;
                height: 45px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                transition: width 0.3s;
            }

            .back-btn:hover {
                width: 120px;
            }

            .back-btn .icon {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                width: 45px;
                height: 45px;
            }

            .back-btn .icon span {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .back-btn span svg path {
                fill: #a5a5a5;
            }

            .icon-label {
                width: 75px;
                height: 75px;
                display: flex;
                align-items: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                color: #a5a5a5;
            }
        </style>
    </head>

    <body>
        <main>
            <div class="content-player bg-dark">
                <a href="{{ route('my.bootcamp.details', $class->bootcamp_slug) }}" class="eBtn gradient back-btn">
                    <div class="icon">
                        <span class="d-inline-flex">
                            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24" width="25" height="25">
                                <path d="m13.646,18.342l-5.281-5.281c-.283-.283-.439-.66-.439-1.061s.156-.777.439-1.061l5.281-5.281.707.707-5.281,5.281c-.094.095-.146.22-.146.354s.052.259.146.354l5.281,5.281-.707.707Z" />
                            </svg>
                        </span>
                    </div>
                    <span class="icon-label">{{ get_phrase('Go Back') }}</span>
                </a>
                <div class="row justify-content-center">
                    <div class="col-auto">
                        <video id="player" playsinline controls oncontextmenu="return false;" controlslist="nodownload">
                            <source src="{{ asset($class->file) }}" type="video/mp4">
                        </video>

                    </div>
                </div>

                <script>
                    "use strict";
                    var player = new Plyr('#player', {
                        youtube: {
                            // Options for YouTube player
                            controls: 1, // Show YouTube controls
                            modestBranding: false, // Show YouTube logo
                            showinfo: 1, // Show video title and uploader on play
                            rel: 0, // Show related videos at the end
                            iv_load_policy: 3, // Do not show video annotations
                            cc_load_policy: 1, // Show captions by default
                            autoplay: false, // Do not autoplay
                            loop: false, // Do not loop the video
                            mute: false, // Do not mute the video
                            start: 0, // Start at this time (in seconds)
                            end: null // End at this time (in seconds)
                        }
                    });
                </script>
            </div>
        </main>
    </body>

    </html>
@endif
