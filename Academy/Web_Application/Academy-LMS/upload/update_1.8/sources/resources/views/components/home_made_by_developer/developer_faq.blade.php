{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <!-- Section Title -->
        <div class="row">
            <div class="col-md-12">
                <div class="dev-section-title">
                    <h1 class="title">
                        <span class="builder-editable" builder-identity="1">{{ get_phrase('Frequently Asked') }}</span>
                        <span class="highlight builder-editable" builder-identity="2">{{ get_phrase('Questions') }}</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- QNA Accordion -->
        <div class="row mb-100">
            <div class="col-md-12">
                <div class="accordion qna-three-accordion" id="accordionExample4">
                    @php
                        $faqs = count(json_decode(get_frontend_settings('website_faqs'), true)) > 0 ? json_decode(get_frontend_settings('website_faqs'), true) : [['question' => '', 'answer' => '']];
                    @endphp
                    @foreach ($faqs as $key => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $key }}">
                                <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#qnaOne{{ $key }}" aria-expanded="true" aria-controls="qnaOne">
                                    {{ $faq['question'] }}
                                </button>
                            </h2>
                            <div id="qnaOne{{ $key }}" class="accordion-collapse collapse px-0  {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#accordionExample4" aria-labelledby="{{ $key }}">
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
