{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-1 fs-32px lh-36px text-center mb-30 builder-editable" builder-identity="1">{{ get_phrase('What the people Thinks About Us') }}</h1>
            </div>
        </div>
        <div class="row mb-100px">
            <div class="col-xl-10 offset-xl-1">
                <div class="testimonial-wrap1">
                    @php
                        $reviews = DB::table('user_reviews')->get();
                    @endphp
                    <div class="testimonial-profile-wrap1 mb-12px">
                        <div class="testimonial-profile-area1 slider-nav">
                            @foreach ($reviews as $review)
                                @php
                                    $userDetails = DB::table('users')->where('id', $review->user_id)->first();
                                @endphp
                                <div class="testimonial-profile1">
                                    <div class="testimonial-profile-img1">
                                        <img src="{{ get_image($userDetails->photo) }}" alt="">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="testimonial-details-wrap1 slide-show">
                        @foreach ($reviews as $review)
                            @php
                                $userDetails = DB::table('users')->where('id', $review->user_id)->first();
                            @endphp
                            <div class="single-testimonial-details1">
                                <h2 class="title-1 fs-20px lh-28px fw-semibold mb-12px text-center">{{ $userDetails->name }}</h2>
                                <div class="testimonial-ratings-1 mb-2 d-flex align-items-center justify-content-center">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $review->rating)
                                            <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                        @else
                                            <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="">
                                        @endif
                                    @endfor
                                </div>
                                <p class="subtitle-1 fs-16px lh-24px text-center mb-20px">{{ $review->review }}</p>
                                <div class="quotation d-flex justify-content-center">
                                    <i class="fi-rr-quote-right text-18px" style="color: #264871;"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
