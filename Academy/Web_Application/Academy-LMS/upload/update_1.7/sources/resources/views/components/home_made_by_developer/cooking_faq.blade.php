{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12 pb-5">
                <h1 class="title-5 fs-32px lh-42px fw-600 mb-5 builder-editable" builder-identity="1">{{ get_phrase('Frequently Asked Questions?') }}</h1>
            </div>
        </div>
        <div class="two-accordion-wrap">
            <div class="row mb-110">
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
                <div class="col-md-6">
                    <div class="accordion qnaaccordion-two" id="accordionExampleLeft">
                        @foreach ($odd_faqs as $key => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeft{{ $key }}" aria-expanded="true" aria-controls="collapseLeft{{ $key }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h2>
                                <div id="collapseLeft{{ $key }}" class="accordion-collapse collapse px-0 {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#accordionExampleLeft">
                                    <div class="accordion-body">
                                        <p class="answer">{{ $faq['answer'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="accordion qnaaccordion-two" id="accordionExampleRight">
                        @foreach ($even_faqs as $key => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRight{{ $key }}" aria-expanded="true" aria-controls="collapseRight{{ $key }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h2>
                                <div id="collapseRight{{ $key }}" class="accordion-collapse collapse px-0 {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#accordionExampleRight">
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
</section>
