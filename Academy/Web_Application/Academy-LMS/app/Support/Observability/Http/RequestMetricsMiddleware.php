<?php

declare(strict_types=1);

namespace App\Support\Observability\Http;

use App\Support\Observability\CorrelationIdStore;
use App\Support\Observability\ObservabilityManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestMetricsMiddleware
{
    public function __construct(
        private readonly ObservabilityManager $observability,
        private readonly CorrelationIdStore $correlationIdStore
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $correlationId = $this->resolveCorrelationId($request);
        $this->correlationIdStore->set($correlationId);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000.0;
        $routeIdentifier = $request->route()?->getName() ?: $request->path();

        $context = [];
        if ($request->user()) {
            $context['user_id'] = $request->user()->getAuthIdentifier();
        }
        if ($request->attributes->has('tenant_id')) {
            $context['tenant_id'] = $request->attributes->get('tenant_id');
        }

        $this->observability->recordHttpRequest(
            $request->getMethod(),
            $routeIdentifier,
            $response->getStatusCode(),
            $durationMs,
            $context
        );

        if (! $response->headers->has('X-Request-Id')) {
            $response->headers->set('X-Request-Id', $correlationId);
        }

        return $response;
    }

    private function resolveCorrelationId(Request $request): string
    {
        $incoming = (string) $request->headers->get('X-Request-Id', '');

        if ($incoming !== '') {
            return $incoming;
        }

        return Str::orderedUuid()->toString();
    }
}
