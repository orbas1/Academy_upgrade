{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row g-28px align-items-center mb-100px">
            <div class="col-xl-5 col-lg-6">
                <div class="community-banner-2">
                    @php
                        $storImage = json_decode(get_homepage_settings('university'));
                    @endphp
                    @if (isset($storImage->faq_image))
                        <img src="{{ asset('uploads/home_page_image/university/' . $storImage->image) }}" alt="">
                    @endif
                </div>
            </div>
            <div class="col-xl-7 col-lg-6">
                <div class="ms-xl-3">
                    <h2 class="title-5 fs-32px lh-42px fw-500 mb-30px builder-editable" builder-identity="1">{{ get_phrase('Creating A Community Of Life Long Learners') }}</h2>
                    <p class="subtitle-5 fs-15px lh-25px mb-30px builder-editable" builder-identity="2">
                        {{ get_phrase("Our LMS goes beyond just providing courses. It's a platform designed to ignite curiosity and empower your lifelong learning journey.  This supportive community provides a space to ask questions, no matter how big or small, and receive insightful answers from experienced learners and subject-matter experts.") }}
                    </p>
                    <p class="subtitle-5 fs-15px lh-25px mb-30px builder-editable" builder-identity="3">
                        {{ get_phrase("Share your own experiences and challenges, and find encouragement and inspiration from others on a similar path. The diverse perspectives within our community will broaden your horizons and challenge your thinking, fostering a deeper understanding and a richer learning experience.  Together, we'll transform learning from a solitary pursuit into a collaborative adventure, where shared knowledge fuels individual growth and collective discovery.") }}
                    </p>
                    <a href="{{ route('about.us') }}" class="btn btn-danger-1 builder-editable" builder-identity="4">{{ get_phrase('Learn more about us') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
