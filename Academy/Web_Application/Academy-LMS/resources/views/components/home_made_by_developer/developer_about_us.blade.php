{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}


<!-- Software Development Area Start -->


@if ($stordetails = json_decode(get_homepage_settings('development')))
    @php
        function highlightLastWord($text)
        {
            $words = explode(' ', $text);
            if (count($words) > 1) {
                $lastWord = array_pop($words);
                return implode(' ', $words) . ' <span class="highlight">' . $lastWord . '</span>';
            }
            return '<span class="highlight">' . $text . '</span>';
        }
    @endphp
    <section>
        <div class="container">
            <div class="row row-20 mb-80 align-items-center">
                <div class="col-lg-6">
                    <div class="software-development-banner">
                        @if (isset($stordetails->image))
                            <img class="builder-editable" builder-identity="1" src="{{ asset('uploads/home_page_image/development/' . $stordetails->image) }}" alt="">
                        @else
                            <img class="builder-editable" builder-identity="2" src="{{ asset('assets/frontend/default/image/soft-dev-banner.webp') }}" alt="">
                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="software-development-details">
                        <h2 class="title builder-editable" builder-identity="3">{!! removeScripts(highlightLastWord($stordetails->title)) !!}</h2>
                        <p class="info mb-20 builder-editable" builder-identity="4">{!! $stordetails->description !!}</p>
                        <a href="{{ route('about.us') }}" class="btn-black-arrow1">
                            <span class="builder-editable" builder-identity="5">{{ get_phrase('Learn More') }}</span>
                            <i class="fi-rr-angle-small-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

<!-- Software Development Area End -->
