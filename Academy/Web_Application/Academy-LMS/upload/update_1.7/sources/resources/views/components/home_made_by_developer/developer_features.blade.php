{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<!-- Learning Coding Area Start -->
<section>
    <div class="container">
        <!-- Section Title -->
        <div class="row">
            <div class="col-md-12">
                <div class="dev-section-title">
                    <h1 class="title mb-20">
                        <span class="builder-editable" builder-identity="1">{{ get_phrase('Start Learning') }}</span>
                        <span class="highlight builder-editable" builder-identity="2">{{ get_phrase('Coding') }}</span>
                        <span class="builder-editable" builder-identity="3">{{ get_phrase('Languages') }}</span>
                    </h1>
                    <p class="info builder-editable" builder-identity="4">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
        </div>
        <div class="row row-20 mb-100 justify-content-center">
            <!-- Card -->
            <div class="col-lg-4 col-md-6">
                <div class="learning-coding-card pt-3">
                    <div class="banner mb-5">
                        <img class="builder-editable" builder-identity="5" src="{{ asset('assets/frontend/default/image/learnig-coding-banner1.webp') }}" alt="">
                    </div>
                    <h4 class="title builder-editable" builder-identity="6">{{ get_phrase('Online Courses') }}</h4>
                    <p class="info builder-editable" builder-identity="7">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
            <!-- Card -->
            <div class="col-lg-4 col-md-6">
                <div class="learning-coding-card pt-3">
                    <div class="banner mb-5">
                        <img class="builder-editable" builder-identity="8" src="{{ asset('assets/frontend/default/image/learnig-coding-banner2.webp') }}" alt="">
                    </div>
                    <h4 class="title builder-editable" builder-identity="9">{{ get_phrase('Top Instructors') }}</h4>
                    <p class="info builder-editable" builder-identity="10">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
            <!-- Card -->
            <div class="col-lg-4 col-md-6">
                <div class="learning-coding-card pt-3">
                    <div class="banner mb-5">
                        <img class="builder-editable" builder-identity="11" src="{{ asset('assets/frontend/default/image/learnig-coding-banner3.webp') }}" alt="">
                    </div>
                    <h4 class="title builder-editable" builder-identity="12">{{ get_phrase('Online Certificates') }}</h4>
                    <p class="info builder-editable" builder-identity="13">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Learning Coding Area End -->
