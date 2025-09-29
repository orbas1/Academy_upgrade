@php
    $brandMeta = $brand ?? [
        'name' => config('messaging.brand.name', config('app.name')),
        'support_email' => config('messaging.brand.support_email'),
        'light_logo_url' => config('messaging.brand.light_logo') ? url(config('messaging.brand.light_logo')) : null,
        'dark_logo_url' => config('messaging.brand.dark_logo') ? url(config('messaging.brand.dark_logo')) : null,
    ];
    $mailLocale = $locale ?? app()->getLocale();
    $pageTitle = $title ?? $brandMeta['name'];
    $preview = trim((string) ($previewText ?? ''));
    $preferencesHref = isset($preferencesUrl) ? url($preferencesUrl) : url(config('messaging.community.preferences_url'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $mailLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f7;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a1a;
        }
        a {
            color: #2563eb;
        }
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f5f5f7;
            padding: 40px 0;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
        }
        .header {
            padding: 32px;
            background: linear-gradient(135deg, #2563eb, #1e3a8a);
            text-align: center;
        }
        .header picture img {
            max-width: 160px;
            height: auto;
        }
        .body {
            padding: 32px;
        }
        .body p {
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .cta-button {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 999px;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
        }
        .footer {
            padding: 24px 32px 32px;
            font-size: 13px;
            color: #6b7280;
            background: #f9fafb;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0b1120;
                color: #f8fafc;
            }
            .wrapper {
                background-color: #0b1120;
            }
            .container {
                background-color: #111827;
            }
            .body p {
                color: #e5e7eb;
            }
            .footer {
                background: #0f172a;
                color: #94a3b8;
            }
        }
    </style>
</head>
<body>
    @if($preview !== '')
        <div class="preheader">{{ $preview }}</div>
    @endif
    <table role="presentation" class="wrapper" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="container" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td class="header">
                            <picture>
                                @if(!empty($brandMeta['dark_logo_url']))
                                    <source srcset="{{ $brandMeta['dark_logo_url'] }}" media="(prefers-color-scheme: dark)">
                                @endif
                                <img src="{{ $brandMeta['light_logo_url'] ?? $brandMeta['dark_logo_url'] ?? '' }}" alt="{{ $brandMeta['name'] }}">
                            </picture>
                        </td>
                    </tr>
                    <tr>
                        <td class="body">
                            @yield('content')
                            <p style="margin-top:32px; font-weight:600;">
                                {{ __('community.common.signature', ['community' => $community ?? $brandMeta['name']], $mailLocale) }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p style="margin-bottom:8px;">
                                <a href="{{ $preferencesHref }}" style="color:inherit; text-decoration:underline;">
                                    {{ __('community.common.manage_preferences', [], $mailLocale) }}
                                </a>
                            </p>
                            @if(!empty($brandMeta['support_email']))
                                <p style="margin:0;">{{ $brandMeta['support_email'] }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
