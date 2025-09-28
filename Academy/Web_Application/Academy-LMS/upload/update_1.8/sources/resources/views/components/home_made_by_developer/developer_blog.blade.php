{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

@if (get_frontend_settings('blog_visibility_on_the_home_page'))
    <section>
        <div class="container">
            <!-- Section Title -->
            <div class="row">
                <div class="col-md-12">
                    <div class="dev-section-title">
                        <h1 class="title mb-20">
                            <span class="builder-editable" builder-identity="1">{{ get_phrase('Get News with') }}</span>
                            <span class="highlight builder-editable" builder-identity="2">{{ get_phrase('Academy') }}</span>
                        </h1>
                        <p class="info builder-editable" builder-identity="3">{{ get_phrase("The industry's standard dummy text ever since the  unknown printer took a galley of type and scrambled") }}</p>
                    </div>
                </div>
            </div>
            <div class="row row-20 mb-100">
                @php
                    $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
                @endphp
                @foreach ($blogs as $key => $blog)
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <a href="{{ route('blog.details', $blog->slug) }}" class="dev-news-link">
                            <div class="dev-news-card">
                                <div class="banner">
                                    <img src="{{ get_image($blog->thumbnail) }}" alt="">
                                </div>
                                <div class="dev-news-card-body">
                                    <h5 class="ellipsis-line-2 title mb-12">{{ ucfirst($blog->title) }}</h5>
                                    <div class="date-comments flex-wrap mb-3 d-flex align-items-center">
                                        <div class="date-wrap d-flex align-items-center">
                                            <img src="{{ asset('assets/frontend/default/image/calendar-black-16.svg') }}" alt="">
                                            <p class="value">{{ $blog->created_at->format('d M, Y') }}</p>
                                        </div>
                                        <div class="comment-wrap mt-0 d-flex align-items-center">
                                            <img src="{{ asset('assets/frontend/default/image/messages-black-16.svg') }}" alt="">
                                            <p class="value">{{ count_comments_by_blog_id($blog->id) }}</p>
                                        </div>
                                    </div>
                                    <p class="info ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>

                                    <p class="text-dark mt-3 text-dev-warning">{{ get_phrase('Read More') }} <i class="fi-br-angle-small-right"></i></p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
