@extends('layouts.default')
@push('title', get_phrase('Ebooks'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <!------------------- Breadcum Area Start  ------>
    <section class="breadcum-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="eNtry-breadcum">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#"> {{ get_phrase('Home') }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page"> {{ get_phrase('Ebooks') }}</li>
                            </ol>
                    </div>
                </div>
                <div class="row align-items-center mt-3">
                    </nav>
                    <h3 class="g-title"> {{ get_phrase('Ebooks') }}</h3>
                </div>
                <div class="col-lg-8 col-md-6 col-sm-6 col-6">
                    <p class="showing-text">
                        {{ get_phrase('Showing') . ' ' . count($ebooks) . ' ' . get_phrase('of') . ' ' . $ebooks->total() . ' ' . get_phrase('data') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="eNtery-item">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-4">
                    @include('frontend.default.ebooks.filter')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="row">
                        @foreach ($ebooks as $ebook)
                            @include('frontend.default.ebooks.card')
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            @if (count($ebooks) > 0)
                <div class="entry-pagination">
                    <nav aria-label="Page navigation example">
                        {{ $ebooks->links() }}
                    </nav>
                </div>
            @endif
            <!-- Pagination -->
        </div>
    </div>
@endsection
@push('js')@endpush
