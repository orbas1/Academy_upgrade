<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HttpCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $config = config('performance.http_cache', []);

        if (! ($config['enabled'] ?? false)) {
            return $response;
        }

        if (! $this->shouldApply($request, $response, $config)) {
            return $response;
        }

        $maxAge = (int) ($config['public_max_age'] ?? 60);
        $staleWhileRevalidate = (int) ($config['stale_while_revalidate'] ?? 0);

        $cacheControl = ['public', 'max-age='.$maxAge];

        if ($staleWhileRevalidate > 0) {
            $cacheControl[] = 'stale-while-revalidate='.$staleWhileRevalidate;
        }

        $response->headers->set('Cache-Control', implode(', ', $cacheControl));

        $varyHeaders = $config['vary'] ?? [];
        if (! empty($varyHeaders)) {
            $response->headers->set('Vary', implode(', ', array_unique($varyHeaders)));
        }

        if (($config['etag'] ?? true) && $this->contentIsHashable($response)) {
            $etag = 'W/"'.sha1($response->getContent()).'"';
            $response->setEtag($etag, true);

            if ($request->headers->get('If-None-Match') === $etag) {
                $response->setNotModified();
            }
        }

        return $response;
    }

    protected function shouldApply(Request $request, Response $response, array $config): bool
    {
        if (! $request->isMethodCacheable()) {
            return false;
        }

        if (! in_array($response->getStatusCode(), $config['status_codes'] ?? [200], true)) {
            return false;
        }

        if (($config['skip_authenticated'] ?? false) && $request->user()) {
            return false;
        }

        if (($config['skip_when_has_cookie'] ?? false) && $request->cookies->count() > 0) {
            return false;
        }

        if ($response->headers->has('Cache-Control')) {
            return false;
        }

        if ($response->headers->has('Set-Cookie')) {
            return false;
        }

        $route = $request->route();
        $routeName = $route?->getName();
        $path = $request->path();

        $rules = $config['rules'] ?? [];
        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                if (($routeName && Str::is($rule, $routeName)) || Str::is($rule, $path)) {
                    return true;
                }

                continue;
            }

            if (is_array($rule)) {
                if (isset($rule['name']) && $routeName && Str::is($rule['name'], $routeName)) {
                    return true;
                }

                if (isset($rule['path']) && Str::is($rule['path'], $path)) {
                    return true;
                }

                if (isset($rule['prefix']) && Str::of($path)->startsWith(trim($rule['prefix'], '/'))) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function contentIsHashable(Response $response): bool
    {
        if ($response->getContent() === false || $response->getContent() === null) {
            return false;
        }

        return ! $response->headers->has('Content-Range');
    }
}
