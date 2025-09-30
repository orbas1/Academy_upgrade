<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireObservabilityToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('observability.prometheus.auth_token');

        if ($token !== '') {
            $provided = (string) $request->headers->get('X-Observability-Token', '');
            if (! hash_equals($token, $provided)) {
                abort(Response::HTTP_FORBIDDEN, 'Metrics token mismatch.');
            }
        }

        return $next($request);
    }
}
