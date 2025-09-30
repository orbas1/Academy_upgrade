@php($locales = $supportedLocales ?? config('localization.supported_locales', []))
@php($currentLocale = $appLocale ?? app()->getLocale())

@once
    @push('css')
        <style>
            .locale-switcher {
                position: fixed;
                top: 1rem;
                right: 1rem;
                background-color: rgba(15, 23, 42, 0.85);
                color: #ffffff;
                padding: 0.5rem 0.75rem;
                border-radius: 0.75rem;
                display: flex;
                gap: 0.5rem;
                align-items: center;
                z-index: 999;
                backdrop-filter: blur(8px);
            }

            .locale-switcher label {
                font-size: 0.875rem;
                margin: 0;
            }

            .locale-switcher select {
                appearance: none;
                background: transparent;
                border: 1px solid rgba(255, 255, 255, 0.35);
                color: #ffffff;
                padding: 0.25rem 1.75rem 0.25rem 0.5rem;
                border-radius: 0.5rem;
            }

            .visually-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            [dir="rtl"] .locale-switcher {
                right: auto;
                left: 1rem;
                flex-direction: row-reverse;
            }

            [dir="rtl"] .locale-switcher select {
                padding: 0.25rem 0.5rem 0.25rem 1.75rem;
            }
        </style>
    @endpush
@endonce

<form
    action="{{ route('locale.update') }}"
    method="POST"
    class="locale-switcher"
    role="form"
    aria-label="{{ __('layout.language_switcher_label') }}"
>
    @csrf
    <label for="app-locale" class="visually-hidden">{{ __('layout.choose_language') }}</label>
    <select
        id="app-locale"
        name="locale"
        aria-describedby="current-locale-description"
    >
        @foreach($locales as $localeCode => $meta)
            <option value="{{ $localeCode }}" @selected($localeCode === $currentLocale)>
                {{ $meta['native'] ?? strtoupper($localeCode) }}
            </option>
        @endforeach
    </select>
    <span id="current-locale-description" class="visually-hidden">
        {{ __('layout.current_language', ['language' => __('layout.language_names.' . $currentLocale)]) }}
    </span>
    <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
</form>
