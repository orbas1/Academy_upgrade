{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

@php
    use Carbon\Carbon;
@endphp
@php
    $blogs = App\Models\Blog::where('status', 1)->orderBy('is_popular', 'desc')->orderBy('id', 'desc')->take(3)->get();
@endphp
@if (get_frontend_settings('blog_visibility_on_the_home_page'))
    <!-- Latest News Area Start -->

    <section class="cooking-news-section">
        <div class="container">
            <div class="cooking-news-main-area">
                <div class="row">
                    <div class="col-md-12">
                        <div class="cooking-section-title">
                            <h3 class="title-5 fs-32px lh-42px fw-600 text-center mb-20 builder-editable" builder-identity="1">{{ get_phrase('Follow The Latest News') }}</h3>
                            <p class="info builder-editable" builder-identity="2">
                                {{ get_phrase('The latest blog highlights the most recent articles, updates, and insights from our platform.') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row row-28">

                    @foreach ($blogs as $key => $blog)
                        <div class="col-lg-6">
                            <a href="{{ route('blog.details', $blog->slug) }}" class="list-news1-link">
                                <div class="list-link1-card d-flex align-items-center">
                                    <div class="banner">
                                        <img class="h-230px" src="{{ get_image($blog->thumbnail) }}" alt="">
                                    </div>
                                    <div class="list-news1-card-body">
                                        <div class="date-wrap d-flex align-items-center">
                                            <img src="{{ asset('assets/frontend/default/image/calendar-green-14.svg') }}" alt="">
                                            <p class="date">{{ Carbon::parse($blog->created_at)->format('F j, Y') }}</p>
                                        </div>
                                        <h4 class="title">{{ ucfirst($blog->title) }}</h4>
                                        <p class="info mb-0 ellipsis-line-2">{{ ellipsis(strip_tags($blog->description), 160) }}</p>
                                        <div class="arrow mt-4">
                                            <img src="{{ asset('assets/frontend/default/image/arrow-right-green-20.svg') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </section>
    <!-- Latest News Area End -->
@endif
