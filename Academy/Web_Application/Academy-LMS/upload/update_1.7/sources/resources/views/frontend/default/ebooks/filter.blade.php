<div class="sidebar">
    <div class="row mb-4">
        <div class="col-6">
            {{ get_phrase('Filter') }}
        </div>
        <div class="col-6 text-end">
            @if (count(request()->all()) > 0 || !empty($category_details))
                <a href="{{ route('ebooks') }}">{{ get_phrase('Clear') }}</a>
            @endif
        </div>
    </div>
    <form class="mb-4" action="{{ route('ebooks') }}" method="get">
        <div class="widget">
            <div class="search">
                <input type="text" class="form-control" name="search" placeholder="{{ get_phrase('Search...') }}"
                    @if (request()->has('search')) value="{{ request()->input('search') }}" @endif>
                <button type="submit" class="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </div>
    </form>

    <!------------------- categories start ------------------->
    <div class="widget">
    <h4 class="widget-title">{{ get_phrase('Categories') }}</h4>
    <ul class="entry-widget" id="parent-category">
        @php
            $parent_categories = App\Models\EbookCategory::get();
            $active_category = request()->route()->parameter('category');
            $route_queries = request()->query();
            $route_queries = collect($route_queries)->except('page')->all();
        @endphp

        @foreach ($parent_categories as $index => $parent_category)
            @php $route_queries['category'] = $parent_category->slug; @endphp

            <li class="category @if ($parent_category->slug == $active_category) active @endif 
                {{ $index >= 6 ? 'extra-category d-none' : '' }}" 
                id="{{ $parent_category->slug }}">
                <a href="{{ route('ebooks', $route_queries) }}"
                    class="d-flex align-items-center justify-content-between">
                    <span>{{ $parent_category->title }}</span>
                    <span>{{ \App\Models\Ebook::where('category_id', $parent_category->id)->count() }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if ($parent_categories->count() > 6)
        <div class="down-text toggle-category" style="cursor: pointer;">
            {{ get_phrase('Show More') }}
        </div>
    @endif
</div>

    <!------------------- categories end ------------------->

    <form action="{{ route('ebooks', request()->route()->parameter('category')) }}" method="get" id="filter-courses">

        @if (request()->has('search'))
            <input type="hidden" name="search" value="{{ request()->input('search') }}">
        @endif


        <!------------------- price filter start ------------------->
        <div class="widget">
            <h4 class="widget-title">{{ get_phrase('Price') }}</h4>
            <ul class="entry-widget">
                @foreach (['paid', 'discount', 'free'] as $price)
                    <li class="filter-item">
                        <div class="form-check">
                            <input class="form-check-input mt-0" type="radio" name="price"
                                value="{{ $price }}" id="price-{{ $price }}"
                                @if (request()->has('price') && request()->input('price') == $price) checked @endif />
                            <label class="form-check-label"
                                for="price-{{ $price }}">{{ get_phrase(ucfirst($price)) }}</label>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <!------------------- price filter end ------------------->

        <!------------------- ratings start ------------------->
        <div class="widget">
            <h4 class="widget-title">{{ get_phrase('Ratings') }}</h4>
            <ul class="entry-widget">
                @for ($i = 5; $i >= 1; $i--)
                    <li class="form-check">
                        <input class="form-check-input" type="radio" name="rating" value="{{ $i }}"
                            id="raging-{{ $i }}" @if (request()->has('rating') && request()->input('rating') == $i) checked @endif />
                        <label class="form-check-label" for="raging-{{ $i }}">
                            <ul class="d-flex g-star g-5">
                                @for ($j = 1; $j <= 5; $j++)
                                    <li @if ($j <= $i) class="color-g" @endif>
                                        <i class="fa fa-star"></i>
                                    </li>
                                @endfor
                            </ul>
                        </label>
                    </li>
                @endfor


            </ul>
        </div>
        <!------------------- ratings end ------------------->
    </form>
</div>


@push('js')
    <script>
        $(document).ready(function() {
            $('#see-more').click(function(e) {
                e.preventDefault();
                $(this).toggleClass('active');
                let show_more = $(this).html();

                if ($(this).hasClass('active')) {
                    $('#parent-category').css('height', 'auto');
                    $(this).css('margin-top', '20px');
                    $(this).text('{{ get_phrase('Show Less') }}');
                } else {
                    $('#parent-category').css('height', '400px');
                    $(this).css('margin-top', '0px');
                    $(this).html('{{ get_phrase('Show More') }}');
                }
            });

            var scrollTop = $(".scrollTop");
            $(scrollTop).click(function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 100);
                return false;
            });

            $('input[type="radio"]').change(function(e) {
                $('#filter-courses').submit();
            });
        });
    </script>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.querySelector('.toggle-category');
        const extraItems = document.querySelectorAll('.extra-category');
        let expanded = false;

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                extraItems.forEach(item => item.classList.toggle('d-none'));
                expanded = !expanded;
                toggleBtn.textContent = expanded ? "{{ get_phrase('Show Less') }}" : "{{ get_phrase('Show More') }}";
            });
        }
    });
</script>


@endpush
