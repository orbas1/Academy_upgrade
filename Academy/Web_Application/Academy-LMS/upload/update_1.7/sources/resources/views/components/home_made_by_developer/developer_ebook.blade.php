{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('mobile_app_link'))
    <section class="programming-ebook-section mb-110">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="programming-ebook-area d-flex align-items-center justify-content-between">
                        <div class="programming-ebook-banner">
                            <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/programming-ebook-banner.webp') }}" alt="">
                        </div>
                        <div class="programming-ebook-details">
                            <h2 class="title">
                                <span class="builder-editable" builder-identity="2">{{ get_phrase('Download our mobile app, start learning') }}</span>
                                <span class="highlight builder-editable" builder-identity="3">{{ get_phrase('Academy') }}</span>
                            </h2>
                            <p class="info mb-30 builder-editable" builder-identity="4">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                            <a href="{{ get_frontend_settings('mobile_app_link') }}" class="btn-black-arrow1">
                                <span class="builder-editable" builder-identity="5">{{ get_phrase('Download Now') }}</span>
                                <i class="fi-rr-angle-small-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
