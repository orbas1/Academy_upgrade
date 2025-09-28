{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row g-4 mb-100px">
            <div class="col-lg-4 col-md-6">
                <div class="lms-service-card-1">
                    <img class="service-card-icon1 builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/bulb-blue-75.svg') }}" alt="">
                    <h5 class="title-2 fs-18px lh-28px fw-semibold text-center mb-12 builder-editable" builder-identity="2">{{ get_phrase('Latest Top Skills') }}</h5>
                    <p class="subtitle-1 fs-16px lh-24px text-center builder-editable" builder-identity="3">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="lms-service-card-1">
                    <img class="service-card-icon1 builder-editable" builder-identity="4" src="{{ asset('assets/frontend/default/image/peoples-star-blue-76.svg') }}" alt="">
                    <h5 class="title-2 fs-18px lh-28px fw-semibold text-center mb-12 builder-editable" builder-identity="5">{{ get_phrase('Industry Experts ') }}</h5>
                    <p class="subtitle-1 fs-16px lh-24px text-center builder-editable" builder-identity="6">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="lms-service-card-1">
                    <img class="service-card-icon1 builder-editable" builder-identity="7" src="{{ asset('assets/frontend/default/image/world-blue-69.svg') }}" alt="">
                    <h5 class="title-2 fs-18px lh-28px fw-semibold text-center mb-12 builder-editable" builder-identity="8">{{ get_phrase('Learning From Anywhere') }}</h5>
                    <p class="subtitle-1 fs-16px lh-24px text-center builder-editable" builder-identity="9">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
