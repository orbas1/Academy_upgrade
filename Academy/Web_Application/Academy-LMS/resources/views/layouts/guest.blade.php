<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $appLocale ?? app()->getLocale()) }}" dir="{{ $appLocaleDirection ?? 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/accessibility.js'])
        @stack('css')
    </head>
    <body class="font-sans text-gray-900 antialiased locale-{{ $appLocale ?? app()->getLocale() }}" data-locale-direction="{{ $appLocaleDirection ?? 'ltr' }}">
        @php($availableLocales = $supportedLocales ?? config('localization.supported_locales', []))
        <x-accessibility.skip-link />
        @if(count($availableLocales) > 1)
            <x-accessibility.locale-switcher />
        @endif
        <x-accessibility.locale-flash />
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div id="main-content" role="main" tabindex="-1" class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
