@extends('emails.layouts.community')

@section('content')
    <p style="margin-top: 0;">{{ $data['message'] ?? __('notifications.community.generic.preview') }}</p>

    @isset($data['metadata'])
        <ul style="padding-left: 18px; margin: 16px 0; color: #334155;">
            @foreach($data['metadata'] as $label => $value)
                <li><strong>{{ ucfirst(str_replace('_', ' ', $label)) }}:</strong> {{ $value }}</li>
            @endforeach
        </ul>
    @endisset

    @isset($data['attachments'])
        <div style="margin-top: 16px;">
            <p style="margin-bottom: 8px; font-weight: 600;">Attachments</p>
            <ul style="padding-left: 18px; margin: 0;">
                @foreach($data['attachments'] as $attachment)
                    <li><a href="{{ $attachment['url'] ?? '#' }}">{{ $attachment['label'] ?? 'Attachment' }}</a></li>
                @endforeach
            </ul>
        </div>
    @endisset
@endsection
