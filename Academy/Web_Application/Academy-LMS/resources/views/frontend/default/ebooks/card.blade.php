<style>
    .ebook-card .courses-img {
	height: auto !important;
}
</style>

<div class="col-lg-4 col-md-6 col-sm-6 mb-30">
    <a href="{{ route('ebook.details', $ebook->slug) }}" class="card Ecard eBar-card ebook-card" style="height: auto;">
        <div class="courses-img" style="height: auto; !important;">
            <img src="{{ get_image($ebook->thumbnail) }}" alt="ebook-thumbnail" class="ebook-thumbnail" style="height: auto; !important;">
            <div class="cText d-flex">
                <h4>
                    @if ($ebook->is_paid == 0)
                        {{ get_phrase('Free') }}
                    @else
                        @if ($ebook->discount_flag == 1)
                            @php $discounted_price = number_format(($ebook->price - $ebook->discounted_price), 2) @endphp
                            {{ currency($discounted_price) }}
                            <del>{{ currency(number_format($ebook->price, 2)) }}</del>
                        @else
                            {{ currency(number_format($ebook->price, 2)) }}
                        @endif
                    @endif
                </h4>
            </div>
        </div>
        <div class="card-body entry-details mt-0">
            <div class="info-card mb-15">
                <div class="creator">
                    <img src="{{ get_image($ebook->photo) }}" alt="author-image">
                    <h5>{{ $ebook->author_name }}</h5>
                </div>
            </div>
            <div class="entry-title">
                <h3 class="w-100 ellipsis-2 mb-0">{{ ucfirst($ebook->title) }}</h3>
            </div>
        </div>
        <div class="learn-more">{{ get_phrase('Learn more') }} <i class="fa-solid fa-arrow-right-long ms-2"></i></div>
    </a>
</div>
