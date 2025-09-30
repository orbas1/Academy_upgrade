<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = array_keys(Config::get('localization.supported_locales', []));
        $defaultLocale = Config::get('localization.default_locale', Config::get('app.locale', 'en'));

        $candidateLocales = [
            $request->session()->get('locale'),
            optional($request->user())->preferred_locale,
            $request->cookie(Config::get('localization.cookie_name', 'app_locale')),
            $request->getPreferredLanguage($supportedLocales),
            $defaultLocale,
        ];

        $locale = $this->resolveLocale($candidateLocales, $supportedLocales, $defaultLocale);

        App::setLocale($locale);
        Carbon::setLocale($locale);

        $direction = Config::get("localization.supported_locales.{$locale}.direction", 'ltr');

        View::share('appLocale', $locale);
        View::share('appLocaleDirection', $direction);
        View::share('supportedLocales', Config::get('localization.supported_locales', []));

        $response = $next($request);

        if ($request->session()->get('locale') !== $locale) {
            $request->session()->put('locale', $locale);
        }

        if ($request->cookie(Config::get('localization.cookie_name', 'app_locale')) !== $locale) {
            Cookie::queue(
                Config::get('localization.cookie_name', 'app_locale'),
                $locale,
                (int) Config::get('localization.cookie_lifetime_minutes', 60 * 24 * 30),
                '/',
                Config::get('session.domain'),
                Config::get('session.secure'),
                false,
                false,
                Config::get('localization.cookie_same_site', 'lax')
            );
        }

        return $response;
    }

    private function resolveLocale(array $candidateLocales, array $supportedLocales, string $fallback): string
    {
        foreach (Arr::where($candidateLocales, static fn ($value) => !empty($value)) as $locale) {
            $normalized = strtolower(str_replace('_', '-', $locale));
            foreach ($supportedLocales as $supported) {
                if ($normalized === strtolower($supported) || str_starts_with($normalized, strtolower($supported) . '-')) {
                    return $supported;
                }
            }
        }

        return $fallback;
    }
}
