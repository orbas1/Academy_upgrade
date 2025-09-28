{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-1 fs-32px lh-36px text-center mb-30 builder-editable" builder-identity="1">{{ get_phrase('Our Latest Blog') }}</h1>
            </div>
        </div>
        @php
            $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
        @endphp
        <div class="row g-20px mb-100px">
            @foreach ($blogs as $key => $blog)
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <a href="{{ route('blog.details', $blog->slug) }}" class="d-block h-100 w-100 max-sm-350px">
                        <div class="lms-1-card">
                            <div class="lms-1-card-body">
                                <div class="grid-view-banner1 mb-14px">
                                    <img class="h-230px" src="{{ get_image($blog->thumbnail) }}" alt="">
                                </div>
                                <div>
                                    <h5 class="title-1 fs-20px lh-28px mb-2 ellipsis-line-2">{{ ucfirst($blog->title) }}</h5>
                                    <p class="subtitle-1 fs-16px lh-24px mb-3 ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>
                                    <p class="link-icon-btn1">
                                        <span>{{ get_phrase('Learn More') }}</span>
                                        <span class="fi-rr-angle-small-right"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
