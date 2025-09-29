@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.mention.intro', [
        'actor' => $actorName ?? $actor ?? __('Someone'),
        'context' => $contextLabel ?? $context ?? __('a conversation'),
        'community' => $community,
    ], $mailLocale))) !!}</p>
    @if(!empty($mentionExcerpt ?? $replyExcerpt ?? null))
        <blockquote style="margin: 20px 0; padding: 12px 16px; background:#f3f4f6; border-radius:8px;">
            <p style="margin:0; font-style:italic;">{{ $mentionExcerpt ?? $replyExcerpt }}</p>
        </blockquote>
    @endif
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? null,
        'label' => __('community.mention.cta', [], $mailLocale),
    ])
    <p>{{ __('community.mention.footer', [], $mailLocale) }}</p>
@endsection
