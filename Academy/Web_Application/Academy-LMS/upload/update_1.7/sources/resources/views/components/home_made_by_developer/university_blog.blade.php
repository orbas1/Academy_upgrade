{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('blog_visibility_on_the_home_page'))
    @php
        $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
    @endphp
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-title-1 mb-50px">
                        <h1 class="title-3 mb-26px fs-40px lh-52px fw-medium text-center builder-editable" builder-identity="1">{{ get_phrase('Our Blog') }}</h1>
                        <p class="subtitle-2 fs-15px lh-24px text-center builder-editable" builder-identity="2">
                            {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                    </div>
                </div>
            </div>
            <div class="row g-20px mb-100px">
                @foreach ($blogs as $key => $blog)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <a href="{{ route('blog.details', $blog->slug) }}" class="blog-post1-link">
                            <div class="blog-post1-inner">
                                <div class="banner">
                                    <img src="{{ get_image($blog->thumbnail) }}" alt="...">
                                </div>
                                <div class="blog-post1-details">
                                    <h3 class="title-5 mb-3 pt-2">{{ ucfirst($blog->title) }}</h3>
                                    <p class="info ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>
                                    <p class="read-more d-flex align-items-center">
                                        <span>{{ get_phrase('Read More') }}</span>
                                        <img src="{{ asset('assets/frontend/default/image/angle-right-black-18.svg') }}" alt="">
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
