<?php

namespace App\Http\Middleware;

use App\Support\Localization\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    public function __construct(private readonly LocaleManager $localeManager)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localeManager->determineLocale($request);
        $this->localeManager->apply($locale);

        /** @var Response $response */
        $response = $next($request);

        $this->localeManager->queuePersistentCookie($locale);

        if ($request->expectsJson()) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }
}
