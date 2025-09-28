{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('blog_visibility_on_the_home_page'))
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="meditation-blog-title-area d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="title builder-editable" builder-identity="1">{{ get_phrase('Blogs') }}</h2>
                        <a href="{{ route('blogs') }}" class="explore-btn1">
                            <span class="text builder-editable" builder-identity="2">{{ get_phrase('See All Blogs') }}</span>
                            <span class="icon">
                                <img src="{{ asset('assets/frontend/default/image/arrow-send-white.svg') }}" alt="">
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row row-30 mb-80">
                @php
                    $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
                @endphp
                @foreach ($blogs as $key => $blog)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <a href="{{ route('blog.details', $blog->slug) }}" class="meditation-blog-link">
                            <div class="meditation-blog-inner">
                                <div class="banner">
                                    <img src="{{ get_image($blog->thumbnail) }}" alt="">
                                </div>
                                <div class="meditation-blog-details">
                                    <h3 class="title info ellipsis-line-2">{{ ucfirst($blog->title) }}</h3>
                                    <p class="info ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>
                                    <p class="read-more d-flex align-items-center">
                                        <span>{{ get_phrase('Read More') }}</span>
                                        <img src="{{ asset('assets/frontend/default/image/arrow-right-black-20.svg') }}" alt="">
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
