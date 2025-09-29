@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.invite.intro', [
        'inviter' => $inviterName ?? $inviter ?? __('Someone'),
        'community' => $community,
        'platform' => $platform ?? ($brand['name'] ?? config('app.name')),
    ], $mailLocale))) !!}</p>
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? null,
        'label' => __('community.invite.cta', [], $mailLocale),
    ])
    @if(!empty($expiryDate ?? null))
        <p>{{ __('community.invite.footer', ['expiry' => $expiryDate], $mailLocale) }}</p>
    @endif
@endsection
