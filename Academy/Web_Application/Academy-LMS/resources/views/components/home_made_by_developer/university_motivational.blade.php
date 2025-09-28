{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title-2 mb-50px">
                    <h1 class="title-5 fs-32px lh-42px fw-500 mb-20px text-center builder-editable" builder-identity="1">{{ get_phrase('Think more clearly') }}</h1>
                    <p class="subtitle-5 fs-15px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="row mb-50px">
            <div class="col-12">
                <div class="lms-event-wrap1">
                    @php
                        $motivational_speeches = json_decode(get_frontend_settings('motivational_speech'), true);
                        $increment = 1;
                    @endphp
                    @foreach ($motivational_speeches as $key => $motivational_speech)
                        <div class="lms-event-single1 d-flex gap-2">
                            @php
                                $admininfo = DB::table('users')->where('role', 'admin')->first();
                            @endphp
                            <div class="lms-event-number">
                                <h1 class="title-5 fs-44px lh-29px fw-500">{{ $increment++ }}</h1>
                            </div>
                            <div class="event-details-banner-wrap w-100 d-flex">
                                <div>
                                    <h3 class="title-5 fs-20px lh-26px fw-500 mb-14px">{{ $motivational_speech['title'] }}</h3>
                                    <div class="d-flex align-items-center gap-12px mb-20px">
                                        <div class="lms-author-sm">
                                            @if ($admininfo->photo)
                                                <img src="{{ get_image($admininfo->photo) }}" alt="">
                                            @else
                                                <img src="{{ asset('uploads/users/admin/placeholder/placeholder.png') }}" alt="">
                                            @endif
                                        </div>
                                        <div class="title-5 fs-13px lh-26px fw-medium">{{ $admininfo->name }}</div>
                                    </div>
                                    <p class="subtitle-5 fs-15px lh-25px">{{ $motivational_speech['description'] }}</p>
                                </div>
                                <div class="lms-event1-banner">
                                    @if ($motivational_speech['image'])
                                        <img src="{{ get_image($motivational_speech['image']) }}" alt="">
                                    @else
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
