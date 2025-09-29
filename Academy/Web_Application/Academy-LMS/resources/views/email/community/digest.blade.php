@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
        $periodLabel = $period ?? $periodLabel ?? __('week');
        $highlights = $highlights ?? $recentHighlights ?? [];
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.digest.intro', [
        'community' => $community,
        'period' => $periodLabel,
    ], $mailLocale))) !!}</p>
    <h3 style="margin:24px 0 12px;">{{ __('community.digest.highlights_heading', [], $mailLocale) }}</h3>
    @if(!empty($highlights))
        <ul style="padding-left:18px; margin:0 0 24px;">
            @foreach($highlights as $item)
                <li style="margin-bottom:12px;">
                    <strong>{{ $item['title'] ?? __('Update') }}</strong>
                    @if(!empty($item['description']))
                        <span style="display:block; color:#4b5563;">{{ $item['description'] }}</span>
                    @endif
                    @if(!empty($item['url']))
                        <a href="{{ $item['url'] }}" style="font-size:14px; color:#2563eb;">
                            {{ __('community.digest.cta', [], $mailLocale) }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p>{{ __('community.digest.empty_highlights', ['period' => $periodLabel], $mailLocale) }}</p>
    @endif
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? $digestUrl ?? null,
        'label' => __('community.digest.cta', [], $mailLocale),
    ])
    <p>{{ __('community.digest.footer', [], $mailLocale) }}</p>
@endsection
