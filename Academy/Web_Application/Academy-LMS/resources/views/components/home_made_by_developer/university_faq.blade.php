{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="pt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title-2 mb-50px">
                    <h1 class="title-5 fs-32px lh-42px fw-500 mb-20px text-center builder-editable" builder-identity="1">{{ get_phrase('Frequently Asked Questions') }}</h1>
                    <p class="subtitle-5 fs-15px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="row g-28px align-items-center mb-100px">
            <div class="col-lg-5">
                <div class="tuition-banner">
                    @if (isset($storImage->faq_image))
                        <img src="{{ asset('uploads/home_page_image/university/' . $storImage->faq_image) }}" alt="">
                    @endif
                </div>
            </div>
            <div class="col-lg-7">
                <div class="lms-1-card rounded-20px">
                    <div class="lms-1-card-body p-40px">
                        <div class="accordion qnaaccordion-five" id="accordionExample5">
                            @php
                                $faqs = count(json_decode(get_frontend_settings('website_faqs'), true)) > 0 ? json_decode(get_frontend_settings('website_faqs'), true) : [['question' => '', 'answer' => '']];
                            @endphp
                            @foreach ($faqs as $key => $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="{{ $key }}">
                                        <button class="accordion-button py-4 {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qnaOne{{ $key }}" aria-expanded="true" aria-controls="qnaOne">
                                            {{ $faq['question'] }}
                                        </button>
                                    </h2>
                                    <div id="qnaOne{{ $key }}" class="accordion-collapse collapse px-0 {{ $key == 0 ? 'show' : '' }}" aria-labelledby="{{ $key }}" data-bs-parent="#accordionExample5">
                                        <div class="accordion-body">
                                            <p class="answer">{{ $faq['answer'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
