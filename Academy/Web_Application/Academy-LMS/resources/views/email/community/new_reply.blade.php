@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.new_reply.intro', [
        'actor' => $actorName ?? $actor ?? __('Someone'),
        'post' => $postTitle ?? $post ?? __('your post'),
        'community' => $community,
    ], $mailLocale))) !!}</p>
    @if(!empty($replyExcerpt ?? null))
        <blockquote style="margin: 20px 0; padding: 12px 16px; background:#f3f4f6; border-radius:8px;">
            <p style="margin:0; font-style:italic;">{{ $replyExcerpt }}</p>
        </blockquote>
    @endif
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? null,
        'label' => __('community.new_reply.cta', [], $mailLocale),
    ])
    <p>{{ __('community.new_reply.footer', [], $mailLocale) }}</p>
@endsection
