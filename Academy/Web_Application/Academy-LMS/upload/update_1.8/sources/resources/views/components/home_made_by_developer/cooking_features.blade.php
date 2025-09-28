{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row mb-110">
            <div class="col-md-12">
                <div class="cooking-services-area">
                    <div class="cooking-single-service">
                        <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/bulb-large.svg') }}" alt="">
                        <h5 class="title builder-editable" builder-identity="2">{{ get_phrase('Latest Top Skills') }}</h5>
                        <p class="info builder-editable" builder-identity="3">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                    </div>
                    <div class="cooking-single-service">
                        <img class="builder-editable" builder-identity="4" src="{{ asset('assets/frontend/default/image/experts-large.svg') }}" alt="">
                        <h5 class="title builder-editable" builder-identity="5">{{ get_phrase('Industry Experts') }}</h5>
                        <p class="info builder-editable" builder-identity="6">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                    </div>
                    <div class="cooking-single-service">
                        <img class="builder-editable" builder-identity="7" src="{{ asset('assets/frontend/default/image/world-large.svg') }}" alt="">
                        <h5 class="title builder-editable" builder-identity="8">{{ get_phrase('Learning From Anywhere') }}</h5>
                        <p class="info builder-editable" builder-identity="9">{{ get_phrase('Awesome  site the top advertising been business.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
