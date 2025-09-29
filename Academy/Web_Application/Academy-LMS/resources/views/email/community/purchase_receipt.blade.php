@extends('email.community.layout')

@section('content')
    @php
        $mailLocale = $locale ?? app()->getLocale();
        $community = $community ?? $communityName ?? ($brand['name'] ?? config('app.name'));
        $recipientDisplay = $recipientName ?? $recipient ?? '';
        $lineItems = $lineItems ?? $items ?? [];
    @endphp
    <p>{{ __('community.common.greeting', ['name' => $recipientDisplay], $mailLocale) }}</p>
    <p>{!! nl2br(e(__('community.purchase_receipt.intro', [
        'community' => $community,
    ], $mailLocale))) !!}</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border-collapse:collapse;">
        <tr>
            <td colspan="3" style="padding-bottom:12px; font-weight:600; border-bottom:1px solid #e5e7eb;">
                {{ __('community.purchase_receipt.items_heading', [], $mailLocale) }}
            </td>
        </tr>
        @forelse($lineItems as $item)
            <tr>
                <td style="padding:12px 0; font-weight:500;">
                    {{ $item['name'] ?? $item['label'] ?? __('Item') }}
                </td>
                <td style="padding:12px 0; text-align:center; color:#6b7280;">
                    {{ $item['quantity'] ?? 1 }}
                </td>
                <td style="padding:12px 0; text-align:right; font-weight:500;">
                    {{ $item['total'] ?? $item['price'] ?? '' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" style="padding:12px 0; color:#6b7280;">
                    —
                </td>
            </tr>
        @endforelse
        <tr>
            <td colspan="2" style="padding-top:12px; font-weight:600; text-align:right; border-top:1px solid #e5e7eb;">
                {{ __('community.purchase_receipt.total_label', [], $mailLocale) }}
            </td>
            <td style="padding-top:12px; text-align:right; font-weight:700;">
                {{ $totalAmount ?? (($amount ?? '0.00') . ' ' . ($currency ?? '')) }}
            </td>
        </tr>
    </table>
    @include('email.community.partials.cta', [
        'url' => $actionUrl ?? $billingUrl ?? null,
        'label' => __('community.purchase_receipt.cta', [], $mailLocale),
    ])
    <p>{{ __('community.purchase_receipt.footer', ['support' => $brand['support_email'] ?? config('messaging.brand.support_email')], $mailLocale) }}</p>
@endsection
