{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('blog_visibility_on_the_home_page'))
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="title-4 fs-34px lh-44px fw-semibold text-center mb-30px builder-editable" builder-identity="1">{{ get_phrase('Our Latest Blog') }}</h1>
                </div>
            </div>
            <div class="row g-28px mb-100px">
                @php
                    $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
                @endphp
                @foreach ($blogs as $key => $blog)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="max-sm-350px">
                            <a href="{{ route('blog.details', $blog->slug) }}" class="mk-blog-banner">
                                <img class="h-230px" src="{{ get_image($blog->thumbnail) }}" alt="">
                            </a>
                            <a href="{{ route('blog.details', $blog->slug) }}" class="mk-blog-body">
                                <div class="lms-1-card rounded-3 lms-card-hover2">
                                    <div class="lms-1-card-body">
                                        <h3 class="title-4 fs-18px lh-26px fw-semibold mb-14px ellipsis-line-2">{{ ucfirst($blog->title) }}</h3>
                                        <p class="subtitle-4 fs-15px lh-24px mb-18px ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>
                                        <div class="card-icon-text3 mk-blog-icontext d-flex align-items-center">
                                            <span class="fi-rr-time-oclock"></span>
                                            <p class="subtitle-4 fs-12px lh-normal">{{ $blog->created_at->format('d M, Y') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
