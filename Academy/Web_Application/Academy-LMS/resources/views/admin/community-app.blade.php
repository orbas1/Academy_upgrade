@extends('layouts.admin')

@push('title')
    {{ __('Communities Control Center') }}
@endpush

@section('content')
    <div id="community-admin-app" class="min-vh-100"></div>
@endsection

@push('js')
    @php
        $encodedContext = json_encode(
            $appContext,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    @endphp
    <script type="application/json" id="community-admin-app-context">{!! $encodedContext !!}</script>
    <script>
        window.__COMMUNITY_ADMIN_APP__ = JSON.parse(
            document.getElementById('community-admin-app-context').textContent,
        );
    </script>
    @vite('resources/js/admin/main.ts')
@endpush
