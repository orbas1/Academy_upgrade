{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('mobile_app_link'))
    <!-- Download Area Start -->
    <section class="home1-download-section">
        <div class="container">
            <div class="row mb-60">
                <div class="col-md-12">
                    <div class="home1-download-area">
                        <h1 class="title builder-editable" builder-identity="1">{{ get_phrase('Download our mobile app, start learning today') }}</h1>
                        <p class="info mb-30 builder-editable" builder-identity="2">{{ get_phrase('Includes all Course && Features') }}</p>
                        <div class="buttons d-flex align-items-center justify-content-center flex-wrap">
                            <a href="{{ get_frontend_settings('mobile_app_link') }}" target="_blank" class="theme-btn1 text-center builder-editable" builder-identity="3">{{ get_phrase('Get Bundle') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Download Area End -->
@endif
