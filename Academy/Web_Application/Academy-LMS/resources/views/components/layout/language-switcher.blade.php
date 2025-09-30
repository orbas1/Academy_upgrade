@php($activeLocale = $currentLocale ?? ['code' => app()->getLocale()])
<form method="POST" action="{{ route('locale.switch') }}" class="language-switcher" aria-label="{{ __('locale.switch_language') }}">
    @csrf
    <label for="language-switcher-select" class="visually-hidden">{{ __('locale.switch_language') }}</label>
    <select id="language-switcher-select" name="locale" class="language-switcher__select" onchange="this.form.submit()" aria-live="polite">
        @foreach ($supportedLocales ?? [] as $code => $locale)
            <option value="{{ $code }}" @selected(($activeLocale['code'] ?? '') === $code)>
                {{ $locale['native_name'] ?? \Illuminate\Support\Str::ucfirst($locale['name'] ?? $code) }}
            </option>
        @endforeach
    </select>
</form>
