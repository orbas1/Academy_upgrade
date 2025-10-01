<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureFeatureIsEnabled
{
    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        $enabled = (bool) config(sprintf('feature-flags.%s', $feature), false);

        if (! $enabled) {
            throw new NotFoundHttpException('Feature not available.');
        }

        return $next($request);
    }
}
