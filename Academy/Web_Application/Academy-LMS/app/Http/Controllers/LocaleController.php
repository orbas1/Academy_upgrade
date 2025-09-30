<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocaleUpdateRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __construct(private readonly UrlGenerator $url)
    {
    }

    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $locale = $validated['locale'];
        $redirectCandidate = $validated['redirect_to'] ?? $request->headers->get('referer');
        $redirectTo = $this->resolveRedirectTarget($redirectCandidate);

        $request->session()->put('locale', $locale);

        $this->persistPreferredLocale($request->user(), $locale);

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

        $request->session()->flash('locale.updated', trans('layout.locale_changed', ['language' => trans("layout.language_names.$locale")]));

        return redirect()->to($redirectTo);
    }

    private function persistPreferredLocale(?Authenticatable $user, string $locale): void
    {
        if (!$user || !method_exists($user, 'forceFill') || !method_exists($user, 'saveQuietly')) {
            return;
        }

        if (method_exists($user, 'isFillable') && !$user->isFillable('preferred_locale')) {
            return;
        }

        $user->forceFill(['preferred_locale' => $locale]);
        $user->saveQuietly();
    }

    private function resolveRedirectTarget(?string $candidate): string
    {
        if (is_string($candidate) && str_starts_with($candidate, '/')) {
            return $candidate;
        }

        if (is_string($candidate)) {
            $parsed = parse_url($candidate);
            if ($parsed !== false && empty($parsed['scheme']) && empty($parsed['host']) && !empty($parsed['path'])) {
                return $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
            }

            $appHost = parse_url($this->url->to('/'), PHP_URL_HOST);
            if ($parsed !== false && ($parsed['host'] ?? null) === $appHost) {
                $path = $parsed['path'] ?? '/';
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                return $path . $query;
            }
        }

        return $this->url->to('/');
    }
}
