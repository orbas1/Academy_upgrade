{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section style="background-color: background: rgba(255, 248, 246, 1);background-color: rgba(255, 248, 246, 1); padding-top: 60px; padding-bottom: 1px; margin-bottom: 50px;">
    <div class="container">
        <!-- Section title -->
        <div class="row">
            <div class="col-md-12">
                <div class="posesjourney-title-area home1-section-title elegant-testimonial-title">
                    <h1 class="title mb-20 fw-500 pb-4 builder-editable" builder-identity="1">{{ get_phrase('What our client say') }}</h1>
                </div>
            </div>
        </div>
    </div>
    <!-- Testimonials -->
    <div class="elegant-testimonials-wrap mb-50">
        <div class="swiper meditation-testimonial-1">
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
                                    <img src="{{ get_image_by_id($userDetails->id) }}" alt="">
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

            <!-- If we need navigation buttons -->
            <div class="row justify-content-center mt-5 pt-5">
                <div class="col-md-6 col-lg-5 col-xl-4 position-relative">
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-scrollbar ms-auto me-auto MT-2"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        </div>

    </div>
</section>
