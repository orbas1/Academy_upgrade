@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.approval.intro', [
        'community' => $community,
    ], $mailLocale))) !!}</p>
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? null,
        'label' => __('community.approval.cta', [], $mailLocale),
    ])
    <p>{{ __('community.approval.footer', [], $mailLocale) }}</p>
@endsection
