@extends('emails.layouts.community')

@section('content')
    <p style="margin-top: 0;">{{ __('notifications.community.member_approved.preview', ['community' => $data['community_name'] ?? 'the community']) }}</p>
    <p style="margin-top: 16px;">{{ $data['message'] ?? 'We are excited to have you. Here are a few quick tips to get started:' }}</p>
    <ol style="margin: 16px 0; padding-left: 18px; color: #334155;">
        <li>Introduce yourself in the welcome thread.</li>
        <li>Review pinned resources tailored for new members.</li>
        <li>Set your notification preferences to match your pace.</li>
    </ol>
@endsection
