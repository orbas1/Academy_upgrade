@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.reminder.intro', [
        'event' => $eventName ?? $event ?? __('your event'),
        'date' => $eventDate ?? $date ?? '',
        'community' => $community,
    ], $mailLocale))) !!}</p>
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? $eventUrl ?? null,
        'label' => __('community.reminder.cta', [], $mailLocale),
    ])
    <p>{{ __('community.reminder.footer', [], $mailLocale) }}</p>
@endsection
