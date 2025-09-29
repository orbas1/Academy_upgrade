@props([
    'url',
    'label',
    'fallback' => __('community.common.cta_fallback', [], $locale ?? app()->getLocale()),
])
@if(!empty($url))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr>
        <td align="left">
            <a href="{{ $url }}" class="cta-button">{{ $label }}</a>
        </td>
    </tr>
</table>
@else
    <p>{{ $fallback }}</p>
@endif
