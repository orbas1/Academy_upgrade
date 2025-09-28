{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <!-- Section Title -->
        <div class="row">
            <div class="col-md-12">
                <div class="dev-section-title">
                    <h1 class="title mb-20">
                        <span class="builder-editable" builder-identity="1">{{ get_phrase('What Our') }}</span>
                        <span class="highlight builder-editable" builder-identity="2">{{ get_phrase('Students') }}</span>
                        <span class="builder-editable" builder-identity="3">{{ get_phrase('Have To Say') }}</span>
                    </h1>
                    <p class="info builder-editable" builder-identity="4">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
        </div>
        <!-- Testimonials -->
        <div class="row mb-100">
            <div class="col-md-12">
                <div class="swiper dev-student-swiper">
                    <div class="swiper-wrapper">
                        @php
                            $reviews = DB::table('user_reviews')->get();
                        @endphp
                        @foreach ($reviews as $review)
                            @php
                                $userDetails = App\Models\User::where('id', $review->user_id)->firstOrNew();
                            @endphp
                            <div class="swiper-slide">
                                <div class="dev-student-testimonial">
                                    <div class="ratings d-flex align-items-center">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                            @else
                                                <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="">
                                            @endif
                                        @endfor
                                    </div>
                                    <p class="feedback"><span class="bold">{{ $review->review }}</p>
                                    <div class="profile-wrap d-flex align-items-center">
                                        <div class="profile">
                                            <img src="{{ get_image_by_id($userDetails->id) }}" alt="">
                                        </div>
                                        <div class="name-role">
                                            <h5 class="name">{{ $userDetails->name }}</h5>
                                            <p class="role">{{ get_phrase('Student') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-wrap d-flex align-items-center justify-content-center">
                        <div class="swiper-button-prev">
                            <i class="fi-rr-arrow-alt-left ms-3 ps-1"></i>
                        </div>
                        <div class="swiper-button-next">
                            <i class="fi-rr-arrow-alt-right ms-3 ps-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
