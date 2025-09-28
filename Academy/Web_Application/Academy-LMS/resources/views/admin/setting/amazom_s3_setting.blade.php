@extends('layouts.admin')
@push('title', get_phrase('Amazon S3 settings'))
@push('meta')@endpush
@push('css')@endpush
@section('content')
    <!-- Mani section header and breadcrumb -->
    <div class="ol-card radius-8px">
        <div class="ol-card-body my-3 py-4 px-20px">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap flex-md-nowrap">
                <h4 class="title fs-16px">
                    <i class="fi-rr-settings-sliders me-2"></i>
                    <span>{{ get_phrase('Amazon S3 Settings') }}</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="ol-card">
            <div class="ol-card-body p-20px mb-3">
                <div class="row">
                    <div class="col-md-7">
                        <form class="required-form" action="{{ route('admin.amazom_s3.settings.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="fpb-7 mb-3">
                                <label for="AWS_ACCESS_KEY_ID" class="form-label ol-form-label">{{ get_phrase('Access key id') }}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ $amazon_s3_data['AWS_ACCESS_KEY_ID'] }}" id="AWS_ACCESS_KEY_ID" name="AWS_ACCESS_KEY_ID" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="AWS_SECRET_ACCESS_KEY" class="form-label ol-form-label">{{get_phrase('Secret access key')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ $amazon_s3_data['AWS_SECRET_ACCESS_KEY'] }}" id="AWS_SECRET_ACCESS_KEY" name="AWS_SECRET_ACCESS_KEY" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="AWS_DEFAULT_REGION" class="form-label ol-form-label">{{get_phrase('Default region')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ $amazon_s3_data['AWS_DEFAULT_REGION'] }}" id="AWS_DEFAULT_REGION" name="AWS_DEFAULT_REGION" required="">
                            </div>
                            <div class="fpb-7 mb-3">
                                <label for="AWS_BUCKET" class="form-label ol-form-label">{{get_phrase('AWS bucket')}}</label>
                                <input type="text" class="form-control ol-form-control" value="{{ $amazon_s3_data['AWS_BUCKET'] }}" id="AWS_BUCKET" name="AWS_BUCKET" required="">
                            </div>
                            <button type="submit" class="btn ol-btn-primary" onclick="checkRequiredFields()">{{ get_phrase('Save') }}</button>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <div class="alert alert-success" role="alert">
                                <h6 class="alert-heading">{{get_phrase('Heads up!')}}</h6>
                                <hr class="my-1">
                               <p class="mb-0 text-14px">
                                {{ get_phrase('Since Amazon S3 is integrated, all lesson files (videos) will be uploaded and served directly from your S3 bucket.') }}
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
