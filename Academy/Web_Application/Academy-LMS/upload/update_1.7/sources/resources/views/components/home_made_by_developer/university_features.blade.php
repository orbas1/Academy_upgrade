{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}

<section>
    <div class="container">
        <div class="row g-28px mb-100px">
            @foreach (App\Models\Category::take(8)->get() as $category)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <a class="w-100" href="{{ route('courses', $category->slug) }}">
                        <div class="lms-service-card-2 max-sm-350px">
                            <div class="service-card-banner-2 mb-20px">
                                @if ($category->thumbnail)
                                    <img src="{{ get_image($category->thumbnail) }}" alt="">
                                @endif
                            </div>
                            <div>
                                <h4 class="title-5 fs-20px lh-28px fw-500 mb-2 text-center">{{ $category->title }}</h4>
                                <p class="subtitle-5 fs-15px lh-25px text-center">{{ count_category_courses($category->id) }} {{ get_phrase('courses') }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach

        </div>
    </div>
</section>
