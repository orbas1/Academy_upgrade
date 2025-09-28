{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-4 fs-34px lh-44px fw-semibold text-center mb-30px builder-editable" builder-identity="1">{{ get_phrase('What the people Thinks About Us') }}</h1>
            </div>
        </div>
        <div class="row mb-50px">
            <div class="col-md-12">
                <div class="swiper lms-testimonial-2">
                    <div class="swiper-wrapper">
                        @php
                            $reviews = DB::table('user_reviews')->get();
                        @endphp
                        @foreach ($reviews as $review)
                            @php
                                $userDetails = App\Models\User::where('id', $review->user_id)->firstOrNew();
                            @endphp
                            <div class="swiper-slide">
                                <div class="lms-1-card rounded-4">
                                    <div class="lms-single-testimonial2">
                                        <div class="d-flex justify-content-between gap-2 mb-14px">
                                            <div class="testimonial-profile-wrap2 d-flex align-items-center ">
                                                <div class="testimonial-profile-2">
                                                    <img src="{{ get_image_by_id($userDetails->id) }}" alt="">
                                                </div>
                                                <div>
                                                    <h4 class="title-4 fs-18px lh-25px fw-semibold mb-5px">{{ $userDetails->name }}</h4>
                                                    <p class="subtitle-4 fs-14px lh-24px">{{ date('h:i A', strtotime($review->created_at)) }}</p>
                                                </div>
                                            </div>
                                            <div class="testimonial-quate-1">
                                                <img src="assets/images/icons/quote.svg" alt="">
                                            </div>
                                        </div>
                                        <p class="subtitle-4 fs-15px lh-24px mb-14px">{{ $review->review }}</p>
                                        <div class="d-flex align-items-center gap-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= $review->rating)
                                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                                @else
                                                    <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="">
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
