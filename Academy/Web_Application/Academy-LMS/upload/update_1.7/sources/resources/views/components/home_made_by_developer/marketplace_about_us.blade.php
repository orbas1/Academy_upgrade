{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


@php
    $settings = get_homepage_settings('marketplace');
    $marketplace = json_decode($settings);
    if ($marketplace && isset($marketplace->instructor)) {
        $instructor = $marketplace->instructor;
    }
@endphp
@if ($marketplace)
    <section>
        <div class="container">
            <div class="row g-28px align-items-center mb-100px">
                <div class="col-lg-5 col-md-6">
                    <div class="video-banner-area1">
                        @if (isset($instructor->image))
                            <img src="{{ asset('uploads/home_page_image/marketplace/' . $instructor->image) }}" alt="">
                            <a href="javascript:void(0);" class="play-btn-2" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#becomeInstructor">
                                <img src="{{ asset('assets/frontend/default/image/play-white-22.svg') }}" alt="">
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-7 col-md-6">
                    <div class="ms-xl-3">
                        <h4 class="title-4 fs-34px lh-44px fw-semibold mb-28px builder-editable" builder-identity="1">{{ $instructor->title }}</h4>
                        <p class="subtitle-4 fs-15px lh-25px mb-28px builder-editable" builder-identity="2">{!! $instructor->description !!}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Popup Modal Area Start -->
    <div class="modal fade instructor-video-modal" id="becomeInstructor" tabindex="-1" aria-labelledby="becomeInstructorLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="becomeInstructorLabel">{{ get_phrase('Video title') }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="instructor-modal-video">
                        <div class="plyr__video-embed" id="becomeInstructorPlyr">
                            <iframe src="{{ $instructor->video_url }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Video Popup Modal Area End -->
@endif

<script>
    "use strict";
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
