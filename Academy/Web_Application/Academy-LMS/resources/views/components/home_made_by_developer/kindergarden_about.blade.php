{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

@if ($stordetails = json_decode(get_homepage_settings('kindergarden')))
    <section>
        <div class="container">
            <div class="row g-28px align-items-center mb-100px">
                <div class="col-lg-6">
                    <div class="community-banner1">
                        @if (isset($stordetails->image))
                            <img class="builder-editable" builder-identity="1" src="{{ asset('uploads/home_page_image/kindergarden/' . $stordetails->image) }}" alt="">
                        @else
                            <img class="builder-editable" builder-identity="2" src="{{ asset('assets/frontend/default/image/community-banner.webp') }}" alt="">
                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <h2 class="title-3 fs-32px lh-42px fw-medium mb-30px builder-editable" builder-identity="3">{{ $stordetails->title }}</h2>
                        <p class="subtitle-3 fs-16px lh-25px mb-30px builder-editable" builder-identity="4">{!! $stordetails->description !!}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
