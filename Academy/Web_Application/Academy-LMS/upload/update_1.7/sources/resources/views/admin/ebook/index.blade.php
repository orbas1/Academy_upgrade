@extends('layouts.admin')
@push('title', get_phrase('Manage Ebook'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <style>
        .dAdmin_profile_img {
            width: 40px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }
    </style>
    <!-- Mani section header and breadcrumb -->
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-3 px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    <span>{{ get_phrase('Manage Ebook') }}</span>
                </h4>
                <a href="{{ route('admin.ebook.create') }}"
                    class="btn ol-btn-outline-secondary d-flex align-items-center cg-10px">
                    <span class="fi-rr-plus"></span>
                    <span>{{ get_phrase('Add new ebook') }}</span>
                </a>
            </div>
        </div>
    </div>
    <!-- Start admin area -->
    <div class="row">
        <div class="col-12">
            <div class="ol-card">
                <div class="ol-card-body p-3">
                    <div class="row mt-3 mb-4 print-d-none">
                        <div class="col-md-6 d-flex align-items-center gap-3">
                            <div class="custom-dropdown ms-2">
                                <button class="dropdown-header btn ol-btn-light">
                                    {{ get_phrase('Export') }}
                                    <i class="fi-rr-file-export ms-2"></i>
                                </button>
                                <ul class="dropdown-list">
                                    <li>
                                        <a class="dropdown-item export-btn" href="#"
                                            onclick="downloadPDF('.print-table', 'admin_list')"><i
                                                class="fi-rr-file-pdf"></i> {{ get_phrase('PDF') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item export-btn" href="#" onclick="window.print();"><i
                                                class="fi-rr-print"></i> {{ get_phrase('Print') }}</a>
                                    </li>
                                </ul>
                            </div>
                            @if (isset($_GET) && count($_GET) > 0)
                                <a href="{{ route('admin.ebooks') }}" class="me-2" data-bs-toggle="tooltip"
                                    title="{{ get_phrase('Clear') }}"><i class="fi-rr-cross-circle"></i></a>
                            @endif
                        </div>
                        <!-- Search and filter -->
                        <div class="col-md-6 mt-3 mt-md-0">
                            <form action="{{ route('admin.ebooks') }}" method="get">
                                <div class="row">
                                    <div class="col-9">
                                        <div class="search-input flex-grow-1">
                                            <input type="text" name="search" value="{{ request('search') }}"
                                                placeholder="{{ get_phrase('Search Title') }}"
                                                class="ol-form-control form-control" />
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <button type="submit" class="btn ol-btn-primary w-100"
                                            id="submit-button">{{ get_phrase('Search') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Data info and Pagination -->
                    <div
                        class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                        <p class="admin-tInfo">
                            {{ get_phrase('Showing') . ' ' . count($ebooks) . ' ' . get_phrase('of') . ' ' . $ebooks->total() . ' ' . get_phrase('data') }}
                        </p>
                    </div>
                    <!-- Table -->
                    @if (count($ebooks) > 0)
                        <div class="table-responsive overflow-auto admin_list" id="admin_list">
                            <table class="table eTable eTable-2 print-table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ get_phrase('Title') }}</th>
                                        <th scope="col">{{ get_phrase('Category') }}</th>
                                        <th scope="col">{{ get_phrase('Publication') }}</th>
                                        <th scope="col">{{ get_phrase('Parchase User') }}</th>
                                        <th scope="col">{{ get_phrase('Price') }}</th>
                                        <th scope="col" class="print-d-none">{{ get_phrase('Status') }}</th>
                                        <th scope="col" class="print-d-none">{{ get_phrase('Options') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ebooks as $key => $ebook)
                                        <tr>
                                            <th scope="row">
                                                <p class="row-number">{{ $key + 1 }}</p>
                                            </th>



                                            <td>
                                                <div
                                                    class="dAdmin_profile_name d-flex align-items-center min-w-200px gap-1 ">

                                                    <div class="dAdmin_profile_img">
                                                        <img class="img-fluid rounded-circle" width="45" height="45"
                                                            src="{{ get_image($ebook->thumbnail) }}" id="blog-thumbnail" />
                                                    </div>
                                                    <div>
                                                        <h4 class="title fs-14px">
                                                            <a
                                                                href="{{ route('admin.ebook.edit', ['id' => $ebook->id]) }}">{{ $ebook->title }}</a>
                                                        </h4>

                                                        <a
                                                            href="{{ route('admin.ebooks', ['admin' => $ebook->user_id]) }}">
                                                            <p class="sub-title2 text-12px">
                                                                {{ get_phrase('admin:') }}
                                                                {{ get_user_info($ebook->user_id)->name }}</p>
                                                            <p class="sub-title2 text-12px">
                                                                {{ get_phrase('Email:') }}
                                                                {{ get_user_info($ebook->user_id)->email }}</p>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>


                                            <td>
                                                @php
                                                    $category = DB::table('ebook_categories')
                                                        ->where('id', $ebook->category_id)
                                                        ->first();
                                                @endphp
                                                <div class="dAdmin_info_name min-w-150px sub-title2 text-12px">
                                                    <p class="">{{ $category->title }}</p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px sub-title2 text-12px">
                                                    <p>{{ $ebook->publication_name }}</p>
                                                    <p class="text-muted">{{ $ebook->edition }}</p>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="dAdmin_info_name min-w-150px sub-title2 text-12px">
                                                    {{-- work now  --}}
                                                    @php
                                                        $purchase_count = App\Models\EbookPurchase::where(
                                                            'ebook_id',
                                                            $ebook->id,
                                                        )->count();
                                                    @endphp
                                                    <p class="text-capital">
                                                        {{ $purchase_count }}
                                                    </p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dAdmin_info_name min-w-150px sub-title2 text-12px">
                                                    @if ($ebook->is_paid == 0)
                                                        <p class="eBadge ebg-soft-success">
                                                            {{ get_phrase('Free') }}
                                                        </p>
                                                    @elseif($ebook->discount_flag == 1)
                                                        <p>{{ currency($ebook->price) }}
                                                            <del>{{ currency($ebook->discounted_price) }}</del>
                                                        </p>
                                                    @else
                                                        <p>{{ currency($ebook->price) }}</p>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="print-d-none">
                                                <div class="dAdmin_info_name min-w-150px">
                                                    <p>
                                                        <span
                                                            class="badge {{ $ebook->status ? 'bg-active' : 'bg-danger' }}">{{ get_phrase($ebook->status ? 'Active' : 'Deactivate') }}
                                                        </span>
                                                    </p>
                                                </div>
                                            </td>

                                            <td class="print-d-none">
                                                <div class="dropdown ol-icon-dropdown ol-icon-dropdown-transparent">
                                                    <button class="btn btn-secondary dropdown-toggle" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="fi-rr-menu-dots-vertical"></span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>

                                                            <a class="dropdown-item " href="#"
                                                                onclick="confirmModal('{{ route('admin.ebook.status', $ebook->id) }}')">{{ get_phrase($ebook->status ? 'Make As Inactive' : 'Make As Active') }}</a>
                                                        </li>
                                                        {{-- <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.ebook.preivew', [$ebook->slug, 'full']) }}">{{ get_phrase('Read Full') }}</a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.ebook.preivew', [$ebook->slug, 'partial']) }}">{{ get_phrase('Read Partial') }}</a>
                                                        </li> --}}
                                                        <li>
                                                            <a class="dropdown-item "
                                                                href="{{ route('admin.ebook.edit', ['id' => $ebook->id]) }}">{{ get_phrase('Edit') }}</a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0) "
                                                                onclick="confirmModal('{{ route('admin.ebook.delete', $ebook->id) }}')">{{ get_phrase('Delete') }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- Data info and Pagination -->
                        <div
                            class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                            <p class="admin-tInfo">
                                {{ get_phrase('Showing') . ' ' . count($ebooks) . ' ' . get_phrase('of') . ' ' . $ebooks->total() . ' ' . get_phrase('data') }}
                            </p>
                            {{ $ebooks->links() }}
                        </div>
                    @else
                        @include('admin.no_data')
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End admin area -->
@endsection
@push('js')@endpush
