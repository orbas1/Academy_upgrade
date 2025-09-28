<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $profile = null): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if (!config('security-headers.enabled', true)) {
            return $response;
        }

        if ($this->shouldBypass($request)) {
            return $response;
        }

        $headers = $this->headersForProfile($profile);

        foreach ($headers as $header => $value) {
            if ($value === null) {
                $response->headers->remove($header);
                $this->recordRemoval($request, $header);
                continue;
            }

            if ($profile === null && $this->headerWasRemoved($request, $header)) {
                $response->headers->remove($header);
                continue;
            }

            if ($profile === null && $response->headers->has($header)) {
                continue;
            }

            $response->headers->set($header, $value);
        }

        return $response;
    }

    /**
     * Determine if the middleware should skip applying headers.
     */
    protected function shouldBypass(Request $request): bool
    {
        if ($request->attributes->get('security_headers.skip', false) === true) {
            return true;
        }

        $exclusions = config('security-headers.exclusions', []);

        $paths = $exclusions['paths'] ?? [];
        foreach ($paths as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        $methods = $exclusions['methods'] ?? [];
        if (!empty($methods) && in_array($request->getMethod(), $methods, true)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the headers for a specific profile.
     */
    protected function headersForProfile(?string $profile): array
    {
        $headers = config('security-headers.headers', []);

        if ($profile === null) {
            return $headers;
        }

        $profiles = config('security-headers.profiles', []);
        if (!array_key_exists($profile, $profiles)) {
            return $headers;
        }

        return array_merge($headers, $profiles[$profile]);
    }

    /**
     * Track when a header has been explicitly removed during the request lifecycle.
     */
    protected function recordRemoval(Request $request, string $header): void
    {
        $removed = $request->attributes->get('security_headers.removed', []);
        $removed[] = strtolower($header);
        $request->attributes->set('security_headers.removed', array_values(array_unique($removed)));
    }

    /**
     * Determine whether a header has been explicitly removed earlier in the pipeline.
     */
    protected function headerWasRemoved(Request $request, string $header): bool
    {
        $removed = $request->attributes->get('security_headers.removed', []);

        return in_array(strtolower($header), $removed, true);
    }

    /**
     * Allow controllers to mark the current request to skip the middleware.
     */
    public static function skip(Request $request): void
    {
        $request->attributes->set('security_headers.skip', true);
    }
}
