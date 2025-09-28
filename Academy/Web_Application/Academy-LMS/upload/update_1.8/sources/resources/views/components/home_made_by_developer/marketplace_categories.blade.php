{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- builder identity and builder editable --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title-4 fs-34px lh-44px fw-semibold mb-50px builder-editable" builder-identity="1">{{ get_phrase('Top Categories') }}</h1>
            </div>
        </div>
        <div class="row g-28px row-cols-xl-5 row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-2 mb-100px">
            @foreach (App\Models\Category::take(5)->get() as $category)
                <div class="col">
                    <a href="{{ route('courses', $category->slug) }}">
                        @if ($category->category_logo)
                            <div class="icon-box-md mb-20px">
                                <img src="{{ get_image($category->category_logo) }}" alt="">
                            </div>
                        @endif
                        <h5 class="title-4 fs-20px lh-28px fw-semibold mb-2">{{ $category->title }}</h5>
                        <p class="subtitle-4 fs-15px lh-23px">{{ count_category_courses($category->id) }} {{ get_phrase('courses') }}</p>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
