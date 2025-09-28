{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row gx-0 gy-4 align-items-end mb-50px">
            <div class="col-xl-4">
                <div class="lg-testimonial-titles mb-50px">
                    <h1 class="title-3 fs-40px lh-52px fw-medium mb-30px text-xl-start text-center builder-editable" builder-identity="1">{{ get_phrase('What they’re saying about our courses') }}</h1>
                    <p class="subtitle-3 fs-16px lh-25px text-xl-start text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Having enjoyed a breathlessly successful 2015, there can be no DJ  dynamic set of teaching tools Billed to be deployed.') }}</p>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="swiper lms-testimonial-1 kindergarden-home">

                    <div class="swiper-wrapper">
                        @php
                            $reviews = DB::table('user_reviews')->get();

                        @endphp
                        @foreach ($reviews as $review)
                            @php
                                $userDetails = DB::table('users')->where('id', $review->user_id)->first();
                                $bgColor = '#fffccf';
                                if ($review->rating >= 4) {
                                    $bgColor = '#ffe8eb';
                                } elseif ($review->rating == 3) {
                                    $bgColor = '#ffedff';
                                } else {
                                    $bgColor = '#fffccf';
                                }
                            @endphp
                            <div class="swiper-slide">
                                <div class="lms-single-testimonial1">
                                    <div class="single-testimonial1-inner" style="--bg-color: {{ $bgColor }}">
                                        <div class="testimonial1-profile-img">
                                            <img src="{{ get_image_by_id($userDetails->id) }}" alt="">
                                        </div>
                                        <div class="d-flex align-items-center gap-6px mb-20px">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= $review->rating)
                                                    <img src="{{ asset('assets/frontend/default/image/star-yellow-14.svg') }}" alt="">
                                                @else
                                                    <img src="{{ asset('assets/frontend/default/image/star.svg') }}" alt="">
                                                @endif
                                            @endfor
                                        </div>
                                        <p class="subtitle-3 fs-16px lh-25px mb-4 text-dark ellipsis-line-7">{!! nl2br(htmlspecialchars($review->review)) !!}</p>
                                        <h4 class="title-3 fs-20px lh-25px fw-normal mb-10px">{{ $userDetails->name }}</h4>
                                        <p class="testimonial1-user-role capitalize">{{ $userDetails->role }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        </div>
    </div>
</section>
