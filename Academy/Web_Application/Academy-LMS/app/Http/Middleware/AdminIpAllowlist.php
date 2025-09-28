<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = config('security.admin_ip_allowlist', []);

        if (! empty($allowlist) && ! in_array($request->ip(), $allowlist, true)) {
            abort(403, get_phrase('Admin access is not allowed from this network.'));
        }

        return $next($request);
    }
}
