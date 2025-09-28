{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <!-- Section title -->
        <div class="row">
            <div class="col-md-12">
                <div class="home1-section-title elegant-testimonial-title">
                    <h1 class="title mb-20 fw-500 builder-editable" builder-identity="1">{{ get_phrase('What the people Thinks About Us') }}</h1>
                    <p class="info builder-editable" builder-identity="2">
                        {{ get_phrase('It highlights feedback and testimonials from users, reflecting their experiences and satisfaction.') }}</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Testimonials -->
    <div class="elegant-testimonials-wrap mb-50">
        <div class="swiper elegant-testimonial-1">
            <div class="swiper-wrapper">
                @php
                    $reviews = DB::table('user_reviews')->get();
                @endphp
                @foreach ($reviews as $review)
                    @php
                        $userDetails = DB::table('users')->where('id', $review->user_id)->first();
                    @endphp
                    <div class="swiper-slide">
                        <div class="elegant-testimonial-slide">
                            <div class="ele-testimonial-profile-area d-flex">
                                <div class="profile">
                                    <img src="{{ get_image($userDetails->photo) }}" alt="">
                                </div>
                                <div class="ele-testimonial-profile-name">
                                    <h6 class="name">{{ $userDetails->name }}</h6>
                                    <p class="time">{{ date('h:i A', strtotime($review->created_at)) }}</p>
                                    <div class="rating d-flex align-items-center">
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
                            <p class="review fw-400">{{ $review->review }}</p>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

    </div>
</section>
