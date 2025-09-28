{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section class="harmony-main-section mb-80">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="harmony-title-area d-flex align-items-start justify-content-between flex-wrap">
                    <h1 class="title builder-editable" builder-identity="1">{{ get_phrase('Featured Courses') }}</h1>
                    <a href="{{ route('admin.courses') }}" class="explore-btn2">
                        <span class="text builder-editable" builder-identity="2">{{ get_phrase('Explore Courses') }}</span>
                        <span class="icon">
                            <img src="{{ asset('assets/frontend/default/image/arrow-send-white.svg') }}" alt="">
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="row row-30">
            @php
                $feature_courses = DB::table('courses')->where('status', 'active')->limit(4)->latest('id')->get();
                $hover_colors = [
                    'linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(0, 255, 143, 0.91) 100%);',
                    'linear-gradient(180deg, rgba(255, 197, 211, 0) 10%, #FF90AA 100%);',
                    'linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(249, 232, 50, 0) 0.01%, #EEEE22 100%);',
                    'linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgb(57 220 254) 100%);',
                ];
            @endphp
            @foreach ($feature_courses->take(8) as $key => $row)
                <a href="{{ route('course.details', $row->slug) }}" class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="single-harmony-yoga">
                        <div class="banner">
                            <img src="{{ get_image($row->thumbnail) }}" alt="">
                        </div>
                        <div class="overlay" style="background: {{ $hover_colors[$key] }};">
                            <p class="name">{{ ucfirst($row->title) }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
