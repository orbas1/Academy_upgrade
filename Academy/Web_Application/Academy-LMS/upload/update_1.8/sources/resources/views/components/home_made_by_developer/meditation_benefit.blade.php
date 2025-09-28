{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<!-- Yoga Benefit Area Start -->
@php
    $bigImage = json_decode(get_homepage_settings('meditation'));
    $settings = get_homepage_settings('meditation');
    if (!$settings) {
        $settings = '{"meditation":[{"banner_title":"","banner_description":"", "image":""}]}';
    }
    $meditation_text = json_decode($settings);
    $meditations = [];
    $maxkey = 0;
    if ($meditation_text && isset($meditation_text->meditation)) {
        $meditations = $meditation_text->meditation;
        $maxkey = count($meditations) > 0 ? max(array_keys((array) $meditations)) : 0;
    }
@endphp
<section>
    <div class="container">
        <div class="row mb-80">
            <div class="col-md-12">
                <div class="yoga-benefit-area">
                    <h2 class="title mb-5 builder-editable" builder-identity="1">{{ get_phrase('The benefit of Yoga Expedition') }}</h2>
                    <div class="yoga-benefits-wrap d-flex align-items-center">
                        <ul class="yoga-benefit-left yoga-benefit-list">
                            @foreach ($meditations as $key => $slider)
                                @if ($key % 2 == 0)
                                    <li>
                                        <div class="yoga-benefit-details">
                                            <h6 class="title">{{ $slider->banner_title }}</h6>
                                            <p class="info">{{ $slider->banner_description }}</p>
                                        </div>
                                        <div class="yoga-benefit-image">
                                            <img src="{{ asset('uploads/home_page_image/meditation/' . $slider->image ?? '') }}" alt="">
                                        </div>
                                    </li>
                                @endif
                            @endforeach

                        </ul>
                        <div class="yoga-benefit-banner">
                            @if (isset($bigImage->big_image))
                                <img class="builder-editable" builder-identity="2" src="{{ asset('uploads/home_page_image/meditation/' . $bigImage->big_image) }}" alt="">
                            @endif
                        </div>
                        <ul class="yoga-benefit-right yoga-benefit-list">
                            @foreach ($meditations as $key => $slider)
                                @if ($key % 2 != 0)
                                    <li>
                                        <div class="yoga-benefit-image">
                                            <img src="{{ asset('uploads/home_page_image/meditation/' . $slider->image ?? '') }}" alt="">
                                        </div>
                                        <div class="yoga-benefit-details">
                                            <h6 class="title">{{ $slider->banner_title }}</h6>
                                            <p class="info">{{ $slider->banner_description }}</p>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Yoga Benefit Area End -->
