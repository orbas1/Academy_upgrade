@extends('layouts.admin')
@push('title', get_phrase('Ebook'))
@push('meta')@endpush
@push('css')@endpush
<style>
    .category-img-container {
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 10px 10px 0px 0px;

        overflow: hidden;
    }

    .category-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }
</style>

@section('content')
    <!-- Mani section header and breadcrumb -->
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-3 px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    <span>{{ get_phrase('Category') }}</span>
                </h4>
                <a href="javascript:void(0)" class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px"
                    onclick="ajaxModal('{{ route('modal', ['admin.ebook_category.create', 'parent_id' => 0]) }}', '{{ get_phrase('Add new category') }}')">
                    <span class="fi-rr-plus"></span>
                    <span>{{ get_phrase('Add Category') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Start Admin area -->
    <div class="row g-4">
        @foreach ($category_list as $category)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="ol-card category-card radious-10px">
                    <div class="category-img-container">
                        <img src="{{ get_image($category->thumbnail) }}" alt="...">
                    </div>

                    <h6 class="title fs-14px mb-12px px-3 pt-3">
                        {{ $category->title }}
                    </h6>
                    <div class="category-footer ol-card-body py-1 pb-2 d-flex align-items-center justify-content-center">
                        <a onclick="ajaxModal('{{ route('modal', ['admin.ebook_category.edit', 'id' => $category->id]) }}', )"
                            class="mx-1 btn text-12px fw-600" data-bs-toggle="tooltip" title="{{ get_phrase('Edit') }}"
                            href="#"><i class="fi fi-rr-pen-clip mx-1"></i>{{ get_phrase('Edit') }}</a>
                        <a href="#"
                            onclick="confirmModal('{{ route('admin.ebook.categories.delete', $category->id) }}',)"
                            class="btn text-12px fw-600" data-bs-toggle="tooltip" title="{{ get_phrase('Delete') }}">
                            <i class="fi-rr-trash"></i>
                            {{ get_phrase('Delete') }}
                        </a>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!-- End Admin area -->
@endsection
@push('js')@endpush
