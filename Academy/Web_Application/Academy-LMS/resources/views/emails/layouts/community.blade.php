@php($locale = $locale ?? app()->getLocale())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? __('notifications.community.generic.subject') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f5f5f7;
            color: #1f2933;
            margin: 0;
            padding: 0;
        }
        .wrapper { width: 100%; padding: 24px; }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #312E81, #6366F1);
            color: #ffffff;
            padding: 32px 32px 24px 32px;
        }
        .header h1 { font-size: 24px; margin: 0; }
        .content { padding: 32px; line-height: 1.6; font-size: 16px; }
        .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 12px 24px;
            margin-top: 24px;
            background: linear-gradient(135deg, #4338CA, #6366F1);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .footer {
            padding: 24px 32px 32px 32px;
            color: #64748b;
            font-size: 14px;
        }
        .digest-item { border-bottom: 1px solid #E2E8F0; padding: 12px 0; }
        .digest-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <h1>{{ $subject ?? __('notifications.community.generic.subject') }}</h1>
            @isset($preview)
                <p style="margin-top: 8px; opacity: 0.85;">{{ $preview }}</p>
            @endisset
        </div>
        <div class="content">
            @yield('content')

            @isset($cta)
                <p style="text-align: center;">
                    <a class="cta" href="{{ $cta['url'] ?? '#' }}">{{ $cta['label'] ?? __('View update') }}</a>
                </p>
            @endisset
        </div>
        <div class="footer">
            <p style="margin: 0;">You are receiving this message because your notification preferences allow updates for this community.</p>
            <p style="margin: 8px 0 0 0;">Manage preferences or unsubscribe from specific events in your account settings.</p>
        </div>
    </div>
</div>
</body>
</html>
