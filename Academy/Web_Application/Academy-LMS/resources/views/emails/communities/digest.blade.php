@extends('emails.layouts.community')

@section('content')
    <p style="margin-top: 0;">{{ __('notifications.digest.'.$frequency.'.preview') }}</p>

    <div style="margin-top: 16px;">
        @foreach($data['items'] ?? [] as $item)
            <div class="digest-item">
                <p style="margin: 0; font-weight: 600;">{{ $item['subject'] ?? '' }}</p>
                <p style="margin: 8px 0 0 0; color: #475569;">{{ $item['message'] ?? '' }}</p>
                @if(!empty($item['cta']['url']))
                    <p style="margin: 8px 0 0 0;">
                        <a href="{{ $item['cta']['url'] }}">{{ $item['cta']['label'] ?? __('View update') }}</a>
                    </p>
                @endif
            </div>
        @endforeach
    </div>
@endsection
