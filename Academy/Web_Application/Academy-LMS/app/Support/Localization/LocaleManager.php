<?php

namespace App\Support\Localization;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LocaleManager
{
    private array $supportedLocales;
    private string $defaultLocale;
    private string $cookieName;
    private int $cookieLifetimeDays;
    private bool $cookieSecure;
    private string $queryParameter;

    public function __construct(ConfigRepository $config)
    {
        $this->supportedLocales = $config->get('localization.supported', []);
        $this->defaultLocale = $config->get('localization.default', config('app.locale', 'en'));
        $this->cookieName = $config->get('localization.cookie.name', 'academy_locale');
        $this->cookieLifetimeDays = (int) $config->get('localization.cookie.lifetime_days', 365);
        $this->cookieSecure = (bool) $config->get('localization.cookie.secure', false);
        $this->queryParameter = $config->get('localization.query_parameter', 'lang');
    }

    public function queryParameter(): string
    {
        return $this->queryParameter;
    }

    public function supported(): array
    {
        return $this->supportedLocales;
    }

    public function supportedLocaleCodes(): array
    {
        return array_keys($this->supportedLocales);
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->supportedLocales);
    }

    public function normalize(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $candidate = Str::lower(str_replace('_', '-', $locale));

        if ($this->isSupported($candidate)) {
            return $candidate;
        }

        foreach ($this->supportedLocales as $code => $details) {
            $aliases = array_map(
                static fn ($alias) => Str::lower(str_replace('_', '-', (string) $alias)),
                $details['aliases'] ?? []
            );

            if (in_array($candidate, $aliases, true)) {
                return $code;
            }
        }

        return null;
    }

    public function determineLocale(Request $request): string
    {
        $candidates = [
            $request->query($this->queryParameter),
            $request->route($this->queryParameter),
            optional($request->user())->preferred_locale ?? null,
            Session::get('language'),
            $request->cookie($this->cookieName),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalize(is_string($candidate) ? $candidate : null);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $headerLocales = $this->parseAcceptLanguage($request->server('HTTP_ACCEPT_LANGUAGE'));

        foreach ($headerLocales as $headerLocale) {
            $normalized = $this->normalize($headerLocale);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return $this->defaultLocale;
    }

    public function apply(string $locale): void
    {
        $resolvedLocale = $this->isSupported($locale) ? $locale : $this->defaultLocale;

        app()->setLocale($resolvedLocale);

        $legacyKey = $this->legacyKeyFor($resolvedLocale);

        if ($legacyKey !== null) {
            Session::put('language', $legacyKey);
        }

        Session::put('app_locale', $resolvedLocale);
    }

    public function queuePersistentCookie(string $locale): void
    {
        Cookie::queue(
            Cookie::make(
                $this->cookieName,
                $locale,
                $this->cookieLifetimeDays * 24 * 60,
                '/',
                null,
                $this->cookieSecure,
                false,
                false,
                'Strict'
            )
        );
    }

    public function current(): array
    {
        $locale = app()->getLocale();

        if (! $this->isSupported($locale)) {
            $locale = $this->defaultLocale;
        }

        return array_merge(
            ['code' => $locale],
            $this->supportedLocales[$locale] ?? []
        );
    }

    public function legacyKeyFor(string $locale): ?string
    {
        return Arr::get($this->supportedLocales, $locale . '.legacy_language_key');
    }

    public function direction(string $locale): string
    {
        return Arr::get($this->supportedLocales, $locale . '.direction', 'ltr');
    }

    /**
     * @return array<int, string>
     */
    private function parseAcceptLanguage(?string $header): array
    {
        if ($header === null || trim($header) === '') {
            return [];
        }

        $locales = [];

        foreach (explode(',', $header) as $part) {
            $segments = explode(';', trim($part));
            $locale = $segments[0];

            if ($locale !== '') {
                $locales[] = $locale;
            }
        }

        return $locales;
    }
}
