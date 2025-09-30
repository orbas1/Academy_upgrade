@extends('layouts.admin')
@push('title', get_phrase('Cloudflare R2 settings'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <!-- Mani section header and breadcrumb -->
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-4 px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    <span>{{ get_phrase('Cloudflare R2 Settings') }}</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="ol-card">
            <div class="ol-card-body p-20px mb-3">
                <div class="row">
                    <div class="col-md-7">
                        @php
                            $r2Data = $amazon_s3_data ?? [];
                            $resolve = fn(string $primary, string $fallback = null) => $r2Data[$primary] ?? ($fallback ? ($r2Data[$fallback] ?? '') : '');
                        @endphp
                        <form class="required-form" action="{{ route('admin.amazom_s3.settings.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="fpb-7 mb-3">
                                <label for="CLOUDFLARE_R2_ACCESS_KEY_ID" class="form-label ol-form-label">{{ get_phrase('Access key id') }}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ old('CLOUDFLARE_R2_ACCESS_KEY_ID', $resolve('CLOUDFLARE_R2_ACCESS_KEY_ID', 'AWS_ACCESS_KEY_ID')) }}" id="CLOUDFLARE_R2_ACCESS_KEY_ID" name="CLOUDFLARE_R2_ACCESS_KEY_ID" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="CLOUDFLARE_R2_SECRET_ACCESS_KEY" class="form-label ol-form-label">{{get_phrase('Secret access key')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ old('CLOUDFLARE_R2_SECRET_ACCESS_KEY', $resolve('CLOUDFLARE_R2_SECRET_ACCESS_KEY', 'AWS_SECRET_ACCESS_KEY')) }}" id="CLOUDFLARE_R2_SECRET_ACCESS_KEY" name="CLOUDFLARE_R2_SECRET_ACCESS_KEY" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="CLOUDFLARE_R2_DEFAULT_REGION" class="form-label ol-form-label">{{get_phrase('Default region')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ old('CLOUDFLARE_R2_DEFAULT_REGION', $resolve('CLOUDFLARE_R2_DEFAULT_REGION', 'AWS_DEFAULT_REGION')) }}" id="CLOUDFLARE_R2_DEFAULT_REGION" name="CLOUDFLARE_R2_DEFAULT_REGION" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="CLOUDFLARE_R2_BUCKET" class="form-label ol-form-label">{{get_phrase('Cloudflare R2 bucket')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ old('CLOUDFLARE_R2_BUCKET', $resolve('CLOUDFLARE_R2_BUCKET', 'AWS_BUCKET')) }}" id="CLOUDFLARE_R2_BUCKET" name="CLOUDFLARE_R2_BUCKET" required="">
                            </div>
                            <button type="submit" class="btn ol-btn-primary" onclick="checkRequiredFields()">{{ get_phrase('Save') }}</button>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <div class="alert alert-success" role="alert">
                                <h6 class="alert-heading">{{get_phrase('Heads up!')}}</h6>
                                <hr class="my-1">
                               <p class="mb-0 text-14px">
                                {{ get_phrase('Since Cloudflare R2 is integrated, all lesson files (videos) will be uploaded and served directly from your R2 bucket.') }}
                            </p>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

<script>
    "use strict";

    function activeTab() {
        $(this).toggleClass("active");
    }
</script>

@push('js')
@endpush
