{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container mb-80 pt-4">
        <!-- Section title -->
        <div class="row">
            <div class="col-md-12">
                <div class="home1-section-title">
                    <h1 class="title fw-500 builder-editable" builder-identity="1">{{ get_phrase('Frequently Asked Questions') }}</h1>
                </div>
            </div>
        </div>
        <!-- QNA Accordion -->
        <div class="row">
            <div class="col-md-12">
                <div class="accordion qnaaccordion-one" id="accordionExample1">
                    @php
                        $faqs = count(json_decode(get_frontend_settings('website_faqs'), true)) > 0 ? json_decode(get_frontend_settings('website_faqs'), true) : [['question' => '', 'answer' => '']];
                    @endphp
                    @foreach ($faqs as $key => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $key }}">
                                <button class="accordion-button  {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qnaOne{{ $key }}" aria-expanded="true" aria-controls="qnaOne">
                                    {{ $faq['question'] }}
                                </button>
                            </h2>
                            <div id="qnaOne{{ $key }}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }} px-0" aria-labelledby="{{ $key }}" data-bs-parent="#accordionExample1">
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
</section>
