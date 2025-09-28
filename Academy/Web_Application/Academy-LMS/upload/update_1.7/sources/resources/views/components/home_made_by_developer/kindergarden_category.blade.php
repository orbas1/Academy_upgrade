{{-- To make a editable image or text need to be add a "builder editable" class and builder identity attribute with a unique value --}}
{{-- "builder identity" and "builder editable" --}}
{{-- builder identity value have to be unique under a single file --}}


<section>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="section-title-1 mb-50px">
                    <h1 class="title-3 mb-20px fs-40px lh-52px fw-medium text-center builder-editable" builder-identity="1">{{ get_phrase('Top Categories') }}</h1>
                    <p class="subtitle-2 fs-16px lh-24px text-center builder-editable" builder-identity="2">
                        {{ get_phrase('Awesome  site. on the top advertising a business online includes assembling Having the most keep.') }}</p>
                </div>
            </div>
        </div>
        <div class="row g-28px mb-100px">
            @foreach (App\Models\Category::take(6)->get() as $category)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('courses', $category->slug) }}" class="w-100">
                        <div class="lms-1-card rounded-4 lms-card-hover1">
                            <div class="lms-1-card-body">
                                <div class="d-flex align-items-center gap-20px">
                                    @if ($category->category_logo)
                                        <div class="bg-icon-card1 bg-color-e9f6ff">
                                            <img src="{{ get_image($category->category_logo) }}" alt="">
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="title-3 fs-20px lh-28px fw-medium">{{ $category->title }}</h4>
                                        <p class="subtitle-2 fs-16px lh-23px fw-medium">{{ count_category_courses($category->id) }} {{ get_phrase('courses') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
