{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row g-20px mb-100px align-items-center">
            <div class="col-xl-5 col-lg-6 order-2 order-lg-1">
                <div>
                    <p class="text-bordered-1 mb-12px builder-editable" builder-identity="1">{{ get_phrase('ABOUT US') }}</p>
                    <h1 class="title-1 fs-32px lh-38px mb-20px builder-editable" builder-identity="2">{{ get_phrase('Know About Academy LMS Learning Platform') }}</h1>
                    <p class="subtitle-1 fs-16px lh-24px mb-26px builder-editable" builder-identity="3">
                        {{ get_phrase('Far far away, behind the word mountains, far from the away countries Vokalia and Consonantia, there live the blind texts.') }}</p>
                    <div class="about-text-items ellipsis-line-12 mb-26px builder-editable" builder-identity="4">
                        {!! ellipsis(removeScripts(get_frontend_settings('about_us')), 160) !!}
                    </div>
                    <a href="{{ route('about.us') }}" class="btn btn-primary-2 builder-editable" builder-identity="5">{{ get_phrase('Learn More') }}</a>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 order-1 order-lg-2">
                <div class="about-area-banner1">
                    <img class="builder-editable" builder-identity="6" src="{{ asset('assets/frontend/default/image/language-banner2.png') }}" alt="">
                </div>
            </div>
        </div>
    </div>
</section>
