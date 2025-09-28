{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="subscribe-area-wrap1 mb-100px">
            <div class="row">
                <div class="col-lg-5">
                    <div class="subscribe-area-banner1">
                        <img class="builder-editable" builder-identity="1" src="{{ asset('assets/frontend/default/image/education.jpg') }}" alt="">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="subscribe-area-1">
                        <h3 class="title-4 fs-28px lh-36px fw-bold text-center text-white mb-14px builder-editable" builder-identity="2">
                            {{ get_phrase('Subscribe to our newsletter to get latest updates') }}</h3>

                        <p class="text-white fw-400 text-center builder-editable" builder-identity="3">{{ get_phrase("Subscribe to stay tuned for new latest updates and offer. Let's do it! ") }}</p>
                        <form action="{{ route('newsletter.store') }}" method="post" class="mt-5">
                            @csrf
                            <div class="subscribe-form-inner d-flex align-items-center justify-content-center">
                                <input type="email" class="form-control sub1-form-control" name="email" placeholder="Enter your email">
                                <button type="submit" class="btn btn-white1 btn-white1-sm builder-editable" builder-identity="4">{{ get_phrase('Subscribe') }}</button>
                            </div>
                        </form>
                        <p class="text-white text-13px fw-300 text-center mt-4 pt-3" style="color: #9CA3AC !important;">{{ get_phrase('Read our privacy policy') }} <a href="{{ route('privacy.policy') }}"><u>{{ get_phrase('Here') }}</u>.</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
