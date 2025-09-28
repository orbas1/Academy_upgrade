@extends('layouts.default')
@push('title', get_phrase('My ebooks'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
<style>
    .g-card .card-head img {
	height: auto;
}
</style>
    <section class="my-course-content">
        <div class="profile-banner-area"></div>
        <div class="container profile-banner-area-container">
            <div class="row">
                @include('frontend.default.student.left_sidebar')

                <div class="col-lg-9 px-4">
                    <h4 class="g-title">{{ get_phrase('My Ebooks') }}</h4>

                    <div class="row mt-5">
                        <div class="row bg-white radius-10">
                            @if (count($my_ebooks) > 0)
                                @foreach ($my_ebooks as $ebook)
                                    <div class="col-lg-4 col-md-4 col-sm-6">
                                        <a href="{{ route('ebook.details', $ebook->slug) }}">
                                            <div class="card Ecard g-card">
                                                <div class="card-head">
                                                    <img src="{{ get_image($ebook->thumbnail) }}" alt="course-thumbnail">
                                                </div>
                                                <div class="card-body entry-details">
                                                    <div class="info-card mb-15">
                                                        <div class="creator">
                                                            <img src="{{ get_image($ebook->user_photo) }}"
                                                                alt="author-image">
                                                            <h5>{{ $ebook->user_name }}</h5>
                                                        </div>
                                                    </div>
                                                    <div class="entry-title ellipsis-2" data-bs-toggle="tooltip"
                                                        title="{{ $ebook->title }}">
                                                        <h3 class="w-100">{{ ucfirst($ebook->title) }}</h3>
                                                    </div>
                                                    <div class="my-course-btn">
                                                        <a href="{{ route('my.ebooks.read', $ebook->slug) }}"
                                                            class="eBtn gradient w-100 text-center mt-3" download>{{ get_phrase('Read Now') }}</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            @else
                                @include('frontend.default.empty')
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Pagination -->
        @if (count($my_ebooks) > 0)
            <div class="entry-pagination">
                <nav aria-label="Page navigation example">
                    {{ $my_ebooks->links() }}
                </nav>
            </div>
        @endif
        <!-- Pagination -->
    </section>
    <!------------ My wishlist area End  ------------>
@endsection
