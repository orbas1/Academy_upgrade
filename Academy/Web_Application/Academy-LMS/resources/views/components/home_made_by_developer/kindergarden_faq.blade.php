{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="section-title-1 mb-50px">
                    <h1 class="title-3 mb-20px fs-40px lh-52px fw-medium text-center builder-editable" builder-identity="1">{{ get_phrase('Frequently Asked Questions') }}</h1>
                    <p class="subtitle-2 fs-16px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="two-accordion-wrap">
            @php
                $faqs = json_decode(get_frontend_settings('website_faqs'), true);
                $faqs = count($faqs) > 0 ? $faqs : [['question' => '', 'answer' => '']];
                $odd_faqs = array_filter(
                    $faqs,
                    function ($v, $k) {
                        return $k % 2 == 0;
                    },
                    ARRAY_FILTER_USE_BOTH,
                );
                $even_faqs = array_filter(
                    $faqs,
                    function ($v, $k) {
                        return $k % 2 != 0;
                    },
                    ARRAY_FILTER_USE_BOTH,
                );
            @endphp

            <div class="row mb-100px">
                <div class="col-md-6">
                    <div class="accordion qnaaccordion-three" id="accordionExampleLeft">
                        @foreach ($odd_faqs as $key => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeft{{ $key }}" aria-expanded="true" aria-controls="collapseLeft{{ $key }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h2>
                                <div id="collapseLeft{{ $key }}" class="accordion-collapse px-0 collapse {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#accordionExampleLeft">
                                    <div class="accordion-body">
                                        <p class="accor-three-answer">{{ $faq['answer'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="accordion qnaaccordion-three" id="accordionExampleRight">
                        @foreach ($even_faqs as $key => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRight{{ $key }}" aria-expanded="true" aria-controls="collapseRight{{ $key }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h2>
                                <div id="collapseRight{{ $key }}" class="accordion-collapse px-0 collapse {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#accordionExampleRight">
                                    <div class="accordion-body">
                                        <p class="accor-three-answer">{{ $faq['answer'] }}</p>
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
