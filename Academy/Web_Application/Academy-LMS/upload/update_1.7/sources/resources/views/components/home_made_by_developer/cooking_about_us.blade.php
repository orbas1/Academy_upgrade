{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section class="pt-4">
    <div class="container pt-5">
        <div class="row g-20px mb-100px align-items-center">
            <div class="col-xl-5 col-lg-6 order-2 order-lg-1">
                <div class="">
                    <h1 class="title-5 fs-32px lh-42px fw-600 mb-20px builder-editable" builder-identity="1">{{ get_phrase('Know About Academy LMS Learning Platform') }}</h1>
                    <div class="builder-editable" builder-identity="2">{!! ellipsis(removeScripts(get_frontend_settings('about_us')), 300) !!}</div>
                    <a href="{{ route('about.us') }}" class="rectangle-btn1 mt-5 builder-editable" builder-identity="3">{{ get_phrase('Learn More') }}</a>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 order-1 order-lg-2">
                <div class="about-area-banner1">
                    <img class="builder-editable" builder-identity="4" src="{{ asset('assets/frontend/default/image/cooking-about-us.png') }}" alt="">
                </div>
            </div>
        </div>
    </div>
</section>

@if ($instructor_speech = json_decode(get_homepage_settings('cooking')))
    <section>
        <div class="container">
            <div class="row mb-110">
                <div class="col-md-12">
                    <div class="become-instructor-area d-flex align-items-center justify-content-between">
                        <div class="become-instructor-video-area">
                            @if (isset($instructor_speech->image))
                                <img class="builder-editable" builder-identity="5" src="{{ asset('uploads/home_page_image/cooking/' . $instructor_speech->image) }}" alt="">
                            @else
                            @endif
                            <a href="javascript:void(0);" class="play-icon" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#becomeInstructor">
                                <img src="{{ asset('assets/frontend/default/image/play-white-large.svg') }}" alt="">
                            </a>
                        </div>
                        <div class="become-instructor-details">
                            <h3 class="title-5 fs-32px lh-42px fw-600 mb-20 builder-editable" builder-identity="6">{{ $instructor_speech->title }}</h3>
                            <p class="info mb-30 builder-editable" builder-identity="7">{!! $instructor_speech->description !!}</p>

                        </div>
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
                            <iframe src="{{ $instructor_speech->video_url }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Video Popup Modal Area End -->
@endif
