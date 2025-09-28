@extends('layouts.default')
@push('title', get_phrase('Ebook Details'))
@push('meta')@endpush
@push('css')@endpush
@section('content')

    @php
        $user = get_user_info($ebook->user_id);
    @endphp
<style>
    .ebook-card .courses-img {
	height: auto !important;
}
.ebook-card-img .card-img-top {
	height: 450px;
    object-fit: inherit;
}
.ebook-details-info {
	padding: 30px;
}
.ebook-details-info .row {
	--bs-gutter-x: inherit;
    row-gap: inherit;
}
.ebook-tab-area {
	margin-top: 115px;
}
.ebook-nav {
	column-gap: 40px;
    font-size: 14px;
}
.ebook-tab-btn{
    font-size: 16px;
}
.tab-content p{
    font-size: 14px;
}
.instructor-name {
	font-size: 20px;
}
</style>
    <!------------------- Breadcum Area Start  ------>
    <section class="breadcum-area playing-breadcum eBookDetails">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 px-4">
                    <div class="eNtry-breadcum mt-5">
                        <nav aria-label="breadcrumb ">
                            <ol class="breadcrumb  ">
                                <li class="breadcrumb-item "><a href="{{ route('home') }}">{{ get_phrase('Home') }}</a></li>
                                <li class="breadcrumb-item "><a href="{{ route('ebooks') }}">{{ get_phrase('Ebooks') }}</a>
                                </li>
                                <li class="breadcrumb-item d-none d-md-inline-block active" aria-current="page">
                                    {{ $ebook->title }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="cards">
                        <div class="ebook-details-info">
                            <div class="row">
                                <div class="col-md-5 ">
                                    <div class="ebook-card-img ecardImage zoom-container"  id="zoom-container">
                                      <a href="{{ get_image($ebook->thumbnail) }}" class="image-popup w-100">
                                          <img src="{{ get_image($ebook->thumbnail) }}" class="card-img-top zoom-image"  id="zoom-img" alt="...">
                                      </a>
                                      <span class="magnific"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    </div>
                                </div>

                                <div class="col-md-7 ">
                                    <div class="content">
                                        <h2 class="ebook-title">{{ $ebook->title }}</h2>
                                        <div class="d-flex align-items-end ePrice  flex-wrap">
                                            @if ($ebook->is_paid == 0)
                                                <h2 class="ebook-new-price">{{ get_phrase('Free') }}</h2>
                                            @else
                                                @if ($ebook->discount_flag == 1)
                                                    @php $discounted_price = number_format(($ebook->price - $ebook->discounted_price), 2) @endphp
                                                    <h2 class="ebook-new-price">
                                                        {{ currency($discounted_price) }}
                                                    </h2>
                                                    <h4 class="ebook-old-price">
                                                        <del>{{ currency(number_format($ebook->price, 2)) }}</del>
                                                    </h4>
                                                @else
                                                    <h2 class="ebook-new-price">
                                                        {{ currency(number_format($ebook->price, 2)) }}
                                                    </h2>
                                                @endif
                                            @endif
                                        </div>
                                        {{-- <div class="">
                                            <div class="info-card mb-15">
                                                <div class="creator">
                                                    <img src="{{ get_image($user->photo) }}" alt="author-image">
                                                    <h5>{{ $user->name }}</h5>
                                                </div>
                                            </div>
                                            <ul class="d-flex">
                                                <li>
                                                    <span class="rating">{{ ebook_instructor_rating($ebook->id) }}</span>
                                                    @for ($i = 1; $i <= 5; $i++)
                                                <li>
                                                    <i class="@if ($i <= ebook_instructor_rating($ebook->id)) fa @else fa-regular @endif fa-star rating-star"
                                                        id="me-{{ $i }}"></i>
                                                </li>
                                                @endfor
                                                </li>
                                            </ul>
                                        </div> --}}
                                        <div class="book-details-info">
                                            <p class="book-details">
                                                {!! $ebook->summary !!}
                                            </p>

                                            {{-- <div class="ebook-data">
                                                <ul>
                                                    <li class="d-flex align-items-center ebook-data-list flex-wrap">
                                                        <span
                                                            class="ebook-left-data">{{ get_phrase('Publication Name') }}</span>
                                                        <span
                                                            class="ebook-right-data">{{ $ebook->publication_name }}</span>
                                                    </li>
                                                    <li class="d-flex align-items-center ebook-data-list flex-wrap">
                                                        <span class="ebook-left-data">
                                                            {{ get_phrase('Published Date') }}</span>
                                                        <span class="ebook-right-data">
                                                            {{ date('d M Y', $ebook->published_date) }}</span>
                                                    </li>
                                                    <li class="d-flex align-items-center ebook-data-list flex-wrap">
                                                        <span class="ebook-left-data">{{ get_phrase('Category') }}</span>
                                                        <span class="ebook-right-data">
                                                            {{ $ebook->category }}</span>
                                                    </li>
                                                </ul>
                                            </div> --}}
                                        </div>
                                        <div class="ebook-btn">
                                            <div class="row gap-3 gap-sm-0">
                                                @auth
                                                    @php
                                                        // Check if the e-book has been purchased by the authenticated user
                                                        $query = DB::table('ebook_purchases')
                                                            ->where('user_id', auth()->user()->id)
                                                            ->where('ebook_id', $ebook->id)
                                                            ->first();

                                                        // Check if there's a pending offline payment for the e-book by the authenticated user
                                                        $pending_ebook_for_payment = DB::table('offline_payments')->where('user_id', auth()->user()->id)->where('status', 0)->where('item_type', 'ebook')->whereJsonContains('items', $ebook->id)->first();
                                                    @endphp

                                                    @if ($ebook->price == 0)
                                                        <!-- Show 'Read Now' for free e-books -->
                                                        <div class="col-sm-6 col-lg-4">
                                                            <a href="{{ route('my.ebooks.read', $ebook->slug) }}"
                                                                class="eBtn btn gradient w-100 text-center">{{ get_phrase('Read Now') }}</a>
                                                        </div>
                                                    @elseif ($query)
                                                        <!-- If the e-book has been purchased, show 'Read Now' -->
                                                        <div class="col-sm-6 col-lg-4">
                                                            <a href="{{ route('my.ebooks.read', $ebook->slug) }}"
                                                                class="eBtn btn gradient w-100 text-center" download="">{{ get_phrase('Read Now') }}</a>
                                                        </div>
                                                    @elseif ($pending_ebook_for_payment)
                                                        <!-- Show 'Read Preview' and 'In progress' if there's a pending offline payment -->
                                                        <div class="col-sm-6 col-lg-4">
                                                            <a  href="javascript:;" onclick="ajaxModal('{{ route('modal', ['frontend.default.ebooks.preview', 'id' => $ebook->id]) }}', '{{ get_phrase('Preview') }}')"
                                                                class="eBtn btn gradient w-100 text-center">{{ get_phrase('Read Preview') }}</a>
                                                        </div>
                                                        <div class="col-sm-6 col-lg-4 ps-sm-0">
                                                            <a href="javascript:void(0);"
                                                                class="eBtn gradient text-center w-100">
                                                                {{ get_phrase('In progress') }}</a>
                                                        </div>
                                                    @else
                                                        <!-- Show 'Read Preview' and 'Buy Now' for paid e-books that haven't been purchased -->
                                                        <div class="col-sm-6 col-lg-4">
                                                            <a href="javascript:;" onclick="ajaxModal('{{ route('modal', ['frontend.default.ebooks.preview', 'id' => $ebook->id]) }}', '{{ get_phrase('Preview') }}')"
                                                                class="eBtn btn gradient w-100 text-center">{{ get_phrase('Read Preview') }}</a>
                                                        </div>
                                                        <div class="col-sm-6 col-lg-4 ps-sm-0">
                                                            <a href="{{ route('ebook.payout', $ebook->id) }}"
                                                                class="eBtn btn gradient w-100 text-center">{{ get_phrase('Buy Now') }}</a>
                                                        </div>
                                                    @endif
                                                @else
                                                    <!-- Show 'Read Preview' and 'Buy Now' if the user is not authenticated -->
                                                    <div class="col-sm-6 col-lg-4">
                                                        <a href="javascript:;" onclick="ajaxModal('{{ route('modal', ['frontend.default.ebooks.preview', 'id' => $ebook->id]) }}', '{{ get_phrase('Preview') }}')"
                                                            class="eBtn btn gradient w-100 text-center">{{ get_phrase('Read Preview') }}</a>
                                                    </div>
                                                    <div class="col-sm-6 col-lg-4 ps-sm-0">
                                                        <a href="{{ route('ebook.payout', $ebook->id) }}"
                                                            class="eBtn btn gradient w-100 text-center">{{ get_phrase('Buy Now') }}</a>
                                                    </div>
                                                @endauth
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!------------------- Breadcum Area End  --------->

    <!------------------- e-books details start -  ------>
    <section class="ebook-tab-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12" id="Overview">
                    <div class="ps-box">
                        <nav class="nav-items">
                            <div class="nav nav-tabs ebook-nav" id="nav-tab" role="tablist">
                                <button class="nav-link active ebook-tab-btn" id="nav-home-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home"
                                    aria-selected="true">{{ get_phrase('Summary') }}</button>
                                <button class="nav-link  ebook-tab-btn" id="nav-profile-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-profile" type="button" role="tab"
                                    aria-controls="nav-profile"
                                    aria-selected="false">{{ get_phrase('Specification') }}</button>
                                {{-- @auth --}}
                                    {{-- @php
                                        $query = DB::table('ebook_purchases')
                                            ->where('ebook_id', $ebook->id)
                                            ->where('user_id', auth()->user()->id)
                                            ->first();
                                    @endphp --}}
                                    {{-- @if ($ebook->user_id == auth()->id() )
                                        <button class="nav-link ebook-tab-btn" id="nav-contact-tab" data-bs-toggle="tab"
                                            data-bs-target="#nav-contact" type="button" role="tab"
                                            aria-controls="nav-contact" aria-selected="false">{{ get_phrase('Review') }}
                                        </button>
                                    @elseif($query) --}}
                                        <button class="nav-link ebook-tab-btn" id="nav-contact-tab" data-bs-toggle="tab"
                                            data-bs-target="#nav-contact" type="button" role="tab"
                                            aria-controls="nav-contact" aria-selected="false">{{ get_phrase('Review') }}
                                        </button>
                                    {{-- @endif --}}
                                {{-- @endauth --}}

                                <button class="nav-link ebook-tab-btn" id="nav-about-tab" data-bs-toggle="tab"
                                    data-bs-target="#nav-about" type="button" role="tab" aria-controls="nav-about"
                                    aria-selected="false">{{ get_phrase('About Author') }} </button>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                                aria-labelledby="nav-home-tab" tabindex="0">
                                <p>{!! $ebook->description !!}</p>
                            </div>
                            <div class="tab-pane fade" id="nav-profile" role="tabpanel"
                                aria-labelledby="nav-profile-tab" tabindex="0">
                                <div class="ebook-specification-data">
                                    <ul>
                                        <li class="d-flex align-items-center ebook-specification-data flex-wrap">
                                            <span class="ebook-specification-left-data">
                                                {{ get_phrase('Publication Name') }}</span>
                                            <span
                                                class="ebook-specification-right-data">{{ $ebook->publication_name }}</span>
                                        </li>
                                        <li class="d-flex align-items-center ebook-specification-data flex-wrap">
                                            <span class="ebook-specification-left-data">
                                                {{ get_phrase('Published Date') }}
                                            </span>
                                            <span
                                                class="ebook-specification-right-data">{{ date('d M, Y', $ebook->published_date) }}

                                            </span>
                                        </li>
                                        <li class="d-flex align-items-center ebook-specification-data flex-wrap">
                                            <span class="ebook-specification-left-data">
                                                {{ get_phrase('Language') }}
                                            </span>
                                            <span class="ebook-specification-right-data">{{ $ebook->language }}
                                            </span>
                                        </li>
                                        <li class="d-flex align-items-center ebook-specification-data flex-wrap">
                                            <span class="ebook-specification-left-data">{{get_phrase('Rating')}}</span>
                                            <span class="ebook-specification-right-data">
                                                <ul class="d-flex">
                                                    <li>
                                                        <span
                                                            class="rating">{{ ebook_instructor_rating($ebook->id) }}</span>
                                                        @for ($i = 1; $i <= 5; $i++)
                                                    <li>
                                                        <i class="@if ($i <= ebook_instructor_rating($ebook->id)) fa @else fa-regular @endif fa-star rating-star"
                                                            id="me-{{ $i }}"></i>
                                                    </li>
                                                    @endfor
                                                </ul>
                                        </li>
                                        <li class="d-flex align-items-center ebook-specification-data flex-wrap">
                                            <span class="ebook-specification-left-data">
                                                {{ get_phrase('Category') }}
                                            </span>
                                            <span class="ebook-specification-right-data">{{ $ebook->category }}
                                            </span>
                                        </li>
                                    </ul>
                                    </span>
                                    </li>
                                    </ul>
                                </div>

                            </div>
                            <div class="tab-pane fade " id="nav-contact" role="tabpanel"
                                aria-labelledby="nav-contact-tab" tabindex="0">
                                 @php
                                    $hasReviewed = \App\Models\EbookReview::where('user_id', auth()->id())->where('ebook_id', $ebook->id)->exists();
                                    $purchaseUser = App\Models\EbookPurchase::where('user_id', auth()->id())->where('ebook_id', $ebook->id)->exists();
                                @endphp

                                @if(auth()->check() && !$hasReviewed && $ebook->user_id !== auth()->id() && $purchaseUser)
                                <div class="ebook-reviw mb-5">
                                    <div class="ebook-form">
                                        <form action="{{ route('ebook.review.store') }}" method="post"
                                            enctype="multipart/form-data" class="write-review">
                                            @csrf
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <p class="description">{{ get_phrase('Write a review : ') }}</p>
                                                <div class="d-flex align-items-center justify-content-end gap-4">
                                                    <ul class="d-flex gap-1 rating-stars">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <li>
                                                                <i class="fa-regular fa-star rating-star"
                                                                    id="id-{{ $i }}"></i>
                                                            </li>
                                                        @endfor
                                                    </ul>
                                                    <span class="gradient"
                                                        id="remove-stars">{{ get_phrase('Remove all') }}</span>
                                                </div>
                                            </div>

                                            <input type="hidden" name="rating" value="0">
                                            <input type="hidden" name="ebook_id" value="{{ $ebook->id }}">
                                            <textarea type="text" name="review" class="form-control mb-3" rows="5"
                                                placeholder="{{ get_phrase('Write your comment ...') }}" required></textarea>
                                            <input type="submit" class="eBtn gradient border-none w-100">
                                        </form>
                                    </div>
                                </div>
                                @endif

                                <div class="reviews-section mt-0">
                                    @if (count($reviews) > 0)
                                        <h4>{{ get_phrase('Reviews') }}</h4>
                                    @endif
                                    @foreach ($reviews as $review)
                                        <div class="reviews-details">
                                            <div class="review-data row">
                                                <div class="rating-side col-12 col-sm-4 col-md-3 col-lg-2">
                                                    <h2 class="user-name">{{ $review->author_name }}</h2>
                                                    <h5 class="date">
                                                        {{ date('d M, Y', strtotime($review->created_at)) }}</h5>
                                                    <h2 class="rating-number">{{ number_format($review->rating, 2) }}
                                                    </h2>
                                                    <div class="ebook-reviw-rating">
                                                        <ul class="d-flex">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <li>
                                                                    <i class="@if ($i <= $review->rating) fa @else fa-regular @endif fa-star rating-star"
                                                                        id="me-{{ $i }}"></i>
                                                                </li>
                                                            @endfor
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div
                                                    class="ebook-details-text col-12 col-sm-8 col-md-9 col-lg-10 mt-5 mt-sm-0">
                                                    <p> {{ $review->review }}</p>

                                                    @auth
                                                        @if (auth()->user()->id == $review->user_id)
                                                            <div class="ebook-details-comment-btn d-flex mt-5 gap-3">
                                                                <div class="edit comment-btn"><a
                                                                        onclick="ajaxModal('{{ route('modal', ['frontend.default.ebooks.review_edit', 'id' => $review]) }}', '{{ get_phrase('Edit Review') }}')"
                                                                        class=""
                                                                        href="javascript: void(0);">{{ get_phrase('Edit') }}</a>
                                                                </div>
                                                                <div class="delete comment-btn">
                                                                    <a onclick="confirmModal('{{ route('review.delete', $review->id) }}')" data-bs-toggle="tooltip"  title="{{ get_phrase('Delete') }}" href="javascript: void(0);">{{ get_phrase('Delete') }}</a>
                                                                </div>
                                                            </div>
                                                        @endif()

                                                    @endauth
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nav-about" role="tabpanel" aria-labelledby="nav-contact-tab"
                                tabindex="0">
                                <div class="row gap-5 gap-md-0">
                                    <div class="col-md-9">
                                        <div class="row gap-5 gap-sm-0 abouts-instructor">
                                            <div class="col-sm-3 col-xl-2">
                                                <div class="ebook-avatar">
                                                    <img src="{{ get_image($user->photo) }}" alt="author-image">
                                                </div>
                                            </div>

                                            <div class="col-sm-9 col-xl-10">
                                                <div class="ebook-instructor-details">
                                                    <h2 class="instructor-name">{{ $user->name }}</h2>
                                                    <p class="instructor-category">
                                                    <p class="description mb-3">
                                                        {{ $user->skills ? implode(', ', array_column(json_decode($user->skills, true), 'value')) : '' }}
                                                    </p>
                                                    </p>
                                                    <div class="mb-3">
                                                        <ul class="row ebook-instructor-motion row-gap-2">
                                                            <td>
                                                                @php
                                                                    $purchase_count = App\Models\EbookPurchase::where(
                                                                        'ebook_id',
                                                                        $ebook->id,
                                                                    )->count();
                                                                @endphp

                                                                <li class="col-6 col-sm-6 col-md-4 col-lg-3">
                                                                    <img src=" {{ asset('assets/frontend/default/image/m1.png') }}"
                                                                        alt="...">
                                                                    {{ $purchase_count }}
                                                                    {{ get_phrase('Purchase') }}
                                                                </li>
                                                                <li class="col-6 col-sm-6 col-md-4 col-lg-3">
                                                                    <img src="{{ asset('assets/frontend/default/image/m2.png') }}"
                                                                        alt="...">
                                                                    {{ user_ebooks($user->id) }} {{ get_phrase('Ebook') }}
                                                                </li>
                                                                <li class="col-6 col-sm-6 col-md-4 col-lg-3">
                                                                    <img src="{{ asset('assets/frontend/default/image/m3.png') }}"
                                                                        alt="...">
                                                                    {{ ebook_review($ebook->id) }}
                                                                    {{ get_phrase('review') }}
                                                                </li>
                                                        </ul>
                                                    </div>
                                                    <div class="about-details">
                                                        <div class="instructor-detatils">
                                                            <p class="text">{{ $user->biography }}</p>
                                                        </div>
                                                        <div class="ebook-social-media-icon d-flex">
                                                            @if ($user->facebook)
                                                                <div class= "social-icon">

                                                                    <a href="{{ $user->facebook }}" target="_blank">
                                                                        <span class="align-items-center">
                                                                            <svg width="20" height="20"
                                                                                viewBox="0 0 11 18" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <path
                                                                                    d="M9.99036 0.330303C9.14973 0.241127 8.30487 0.19773 7.45953 0.200303C4.65703 0.200303 2.91536 1.9853 2.91536 4.85864V6.80947H0.561198C0.450691 6.80947 0.34471 6.85337 0.26657 6.93151C0.18843 7.00965 0.144531 7.11563 0.144531 7.22614V10.4345C0.144531 10.545 0.18843 10.651 0.26657 10.7291C0.34471 10.8072 0.450691 10.8511 0.561198 10.8511H2.91536V17.2845C2.91536 17.395 2.95926 17.501 3.0374 17.5791C3.11554 17.6572 3.22152 17.7011 3.33203 17.7011H6.64703C6.75754 17.7011 6.86352 17.6572 6.94166 17.5791C7.0198 17.501 7.0637 17.395 7.0637 17.2845V10.8511H9.41037C9.51152 10.8512 9.60925 10.8144 9.6853 10.7477C9.76136 10.681 9.81055 10.5889 9.8237 10.4886L10.2379 7.2803C10.2456 7.2215 10.2406 7.16172 10.2234 7.10498C10.2062 7.04823 10.177 6.99582 10.1379 6.95125C10.0987 6.90668 10.0505 6.87097 9.99651 6.84652C9.94247 6.82207 9.88384 6.80944 9.82453 6.80947H7.0637V5.17697C7.0637 4.36864 7.2262 4.03114 8.23703 4.03114H9.9362C10.0467 4.03114 10.1527 3.98724 10.2308 3.9091C10.309 3.83096 10.3529 3.72498 10.3529 3.61447V0.743637C10.3529 0.642477 10.3162 0.544754 10.2495 0.4687C10.1828 0.392645 10.0907 0.343454 9.99036 0.330303ZM9.51953 3.19697L8.2362 3.1978C6.4387 3.1978 6.23036 4.3278 6.23036 5.17697V7.22697C6.23036 7.33733 6.27415 7.44319 6.35211 7.5213C6.43007 7.59942 6.53584 7.64341 6.6462 7.64364H9.3512L9.0437 10.0186H6.64703C6.53652 10.0186 6.43054 10.0625 6.3524 10.1407C6.27426 10.2188 6.23036 10.3248 6.23036 10.4353V16.867H3.7487V10.4353C3.7487 10.3248 3.7048 10.2188 3.62666 10.1407C3.54852 10.0625 3.44254 10.0186 3.33203 10.0186H0.978698V7.64364H3.33203C3.44254 7.64364 3.54852 7.59974 3.62666 7.5216C3.7048 7.44346 3.7487 7.33748 3.7487 7.22697V4.85864C3.7487 2.46364 5.1362 1.03364 7.45953 1.03364C8.30036 1.03364 9.05953 1.07947 9.51953 1.11947V3.19697Z"
                                                                                    fill="#192335" />
                                                                            </svg>
                                                                        </span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if ($user->twitter)
                                                                <div class="social-icon">

                                                                    <a href="{{ $user->twitter }}"target="_blank">
                                                                        <span>
                                                                            <svg width="20" height="20"
                                                                                viewBox="0 0 18 16" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <path
                                                                                    d="M1.00195 0.476562L7.19913 8.76272L0.962891 15.4998H2.36648L7.82638 9.6013L12.2378 15.4998H17.0141L10.4682 6.7474L16.2729 0.476562H14.8693L9.84116 5.90882L5.77842 0.476562H1.00195ZM3.06613 1.51044H5.26033L14.9497 14.4657H12.7555L3.06613 1.51044Z"
                                                                                    fill="#192335" />
                                                                            </svg>
                                                                        </span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if ($user->linkedin)
                                                                <div class="social-icon">
                                                                    <a href="{{ $user->linkedin }}"target="_blank">
                                                                        <span>
                                                                            <svg width="20" height="20"
                                                                                viewBox="0 0 16 16" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <path
                                                                                    d="M8 4.40625C7.28922 4.40625 6.59441 4.61702 6.00342 5.01191C5.41243 5.40679 4.95181 5.96806 4.67981 6.62473C4.40781 7.2814 4.33664 8.00399 4.4753 8.70111C4.61397 9.39822 4.95624 10.0386 5.45884 10.5412C5.96143 11.0438 6.60178 11.386 7.29889 11.5247C7.99601 11.6634 8.7186 11.5922 9.37527 11.3202C10.0319 11.0482 10.5932 10.5876 10.9881 9.99658C11.383 9.40559 11.5938 8.71078 11.5938 8C11.5938 7.04688 11.2151 6.13279 10.5412 5.45884C9.86721 4.78488 8.95312 4.40625 8 4.40625ZM8 10.6562C7.47464 10.6562 6.96108 10.5005 6.52427 10.2086C6.08745 9.91672 5.74699 9.50187 5.54595 9.0165C5.3449 8.53114 5.2923 7.99705 5.39479 7.48179C5.49728 6.96653 5.75026 6.49323 6.12175 6.12175C6.49323 5.75026 6.96653 5.49728 7.48179 5.39479C7.99705 5.2923 8.53114 5.3449 9.0165 5.54595C9.50187 5.74699 9.91672 6.08745 10.2086 6.52427C10.5005 6.96108 10.6562 7.47464 10.6562 8C10.6542 8.70385 10.3737 9.37828 9.87597 9.87597C9.37828 10.3737 8.70385 10.6542 8 10.6562ZM11.4375 0.34375H4.5625C3.44362 0.34375 2.37056 0.788224 1.57939 1.57939C0.788224 2.37056 0.34375 3.44362 0.34375 4.5625V11.4375C0.34375 12.5564 0.788224 13.6294 1.57939 14.4206C2.37056 15.2118 3.44362 15.6562 4.5625 15.6562H11.4375C12.5564 15.6562 13.6294 15.2118 14.4206 14.4206C15.2118 13.6294 15.6562 12.5564 15.6562 11.4375V4.5625C15.6562 3.44362 15.2118 2.37056 14.4206 1.57939C13.6294 0.788224 12.5564 0.34375 11.4375 0.34375ZM14.7188 11.4375C14.7188 12.3077 14.373 13.1423 13.7577 13.7577C13.1423 14.373 12.3077 14.7188 11.4375 14.7188H4.5625C3.69226 14.7188 2.85766 14.373 2.24231 13.7577C1.62695 13.1423 1.28125 12.3077 1.28125 11.4375V4.5625C1.28125 3.69226 1.62695 2.85766 2.24231 2.24231C2.85766 1.62695 3.69226 1.28125 4.5625 1.28125H11.4375C12.3077 1.28125 13.1423 1.62695 13.7577 2.24231C14.373 2.85766 14.7188 3.69226 14.7188 4.5625V11.4375ZM12.8438 3.9375C12.8438 4.09202 12.7979 4.24306 12.7121 4.37154C12.6262 4.50002 12.5042 4.60015 12.3615 4.65928C12.2187 4.71841 12.0616 4.73388 11.9101 4.70374C11.7585 4.67359 11.6193 4.59919 11.5101 4.48993C11.4008 4.38067 11.3264 4.24146 11.2963 4.08991C11.2661 3.93837 11.2816 3.78128 11.3407 3.63853C11.3998 3.49577 11.5 3.37376 11.6285 3.28791C11.7569 3.20207 11.908 3.15625 12.0625 3.15625C12.2697 3.15625 12.4684 3.23856 12.6149 3.38507C12.7614 3.53159 12.8438 3.7303 12.8438 3.9375Z"
                                                                                    fill="#192335" />
                                                                            </svg>
                                                                        </span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if (count($related_ebooks) > 0)
                <div class="related-ebooks">
                    <div class="d-flex related-ebooks-wrap flex-wrap column-gap-2 row-gap-3">
                        <div>
                            <h2>{{ get_phrase('Related Ebooks') }}</h2>
                        </div>
                        <div class="ebook-buy-btn related-ebooks-btn">
                            <a href="{{ route('ebooks') }}"
                                class="eBtn btn gradient">{{ get_phrase('View All Ebooks') }}</a>
                        </div>
                    </div>
                </div>
            @endif

            <div class=" row related-ebooks-card">
                @foreach ($related_ebooks as $related_ebook)
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-30">
                        <a href="{{ route('ebook.details', $related_ebook->slug) }}"
                            class="card Ecard eBar-card ebook-card"  style="height:auto;">
                            <div class="courses-img" style="height:auto;">
                                <img src="{{ get_image($related_ebook->thumbnail) }}" alt="ebook-thumbnail"
                                    class="ebook-thumbnail">
                                <div class="cText d-flex">
                                    <h4>
                                        @if ($related_ebook->is_paid == 0)
                                            {{ get_phrase('Free') }}
                                        @else
                                            @if ($related_ebook->discount_flag == 1)
                                                @php $discounted_price = number_format(($related_ebook->price - $related_ebook->discounted_price), 2) @endphp
                                                {{ currency($discounted_price) }}
                                                <del>{{ currency(number_format($related_ebook->price, 2)) }}</del>
                                            @else
                                                {{ currency(number_format($related_ebook->price, 2)) }}
                                            @endif
                                        @endif
                                    </h4>
                                </div>
                            </div>
                            <div class="card-body entry-details mt-0">
                                <div class="info-card mb-15">
                                    <div class="creator">
                                        <img src="{{ get_image($related_ebook->img) }}" alt="author-image">
                                        <h5>{{ $related_ebook->author_name }}</h5>
                                    </div>
                                </div>
                                <div class="entry-title">
                                    <h3 class="w-100 ellipsis-2 mb-0">{{ $related_ebook->title }}</h3>
                                </div>
                            </div>
                            <div class="learn-more">{{get_phrase('Learn more')}} <i class="fa-solid fa-arrow-right-long ms-2"></i></div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!------------------- e-books details end ------->



@endsection
@push('js')
    <script>
        $(document).ready(function() {
            let rating_stars = $('.rating-stars i');
            rating_stars.click(function(e) {
                e.preventDefault();
                let star = $(this).attr('id').substring(3);
                $('.write-review input[name="rating"]').val(star);

                rating_stars.removeClass('fa').addClass('fa-regular');
                for (let i = 1; i <= star; i++) {
                    $('#id-' + i).removeClass('fa-regular').addClass('fa');
                }
            });

            $('#remove-stars').click(function(e) {
                e.preventDefault();
                rating_stars.removeClass('fa fa-regular').addClass('fa-regular');
                $('.write-review input[name="rating"]').val(0);
            });
        });
    </script>


<script>
  const container = document.getElementById('zoom-container');
  const img = document.getElementById('zoom-img');

  container.addEventListener('mousemove', function(e) {
    const rect = container.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width * 100;
    const y = (e.clientY - rect.top) / rect.height * 100;

    img.style.transformOrigin = `${x}% ${y}%`;
    img.style.transform = 'scale(2)';
  });

  container.addEventListener('mouseleave', function() {
    img.style.transform = 'scale(1)';
  });
</script>
<script>
  $(document).ready(function() {
    $('.image-popup').magnificPopup({
      type: 'image',
      closeOnContentClick: true,
      mainClass: 'mfp-img-mobile',
      image: {
        verticalFit: true,
        titleSrc: 'alt'
      },
      zoom: {
        enabled: true,
        duration: 300 
      }
    });

    $('.magnific').on('click', function() {
      $(this).siblings('.image-popup').click();
    });
  });
</script>

@endpush
