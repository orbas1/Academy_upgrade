<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $payload = $this->sanitizePayload($request);
        $payloadJson = $payload ? json_encode($payload, JSON_UNESCAPED_SLASHES) : null;
        $maxLength = (int) config('compliance.max_payload_length', 2000);

        if ($payloadJson && strlen($payloadJson) > $maxLength) {
            $payloadJson = mb_substr($payloadJson, 0, $maxLength) . '…';
        }

        $metadata = array_filter([
            'route' => $request->route()?->getName(),
            'uri' => $request->getRequestUri(),
            'payload' => $payloadJson,
            'query' => $request->query(),
            'request_id' => $request->headers->get('X-Request-Id') ?: (string) Str::uuid(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'actor_role' => $user->role,
            'action' => $request->route()?->getActionName() ?? 'admin.request',
            'http_method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }

    private function sanitizePayload(Request $request): array
    {
        $payload = $request->except(['password']);
        $sensitiveKeys = config('compliance.redacted_fields', []);

        if (empty($payload)) {
            return [];
        }

        array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
            if ($value instanceof UploadedFile) {
                $value = sprintf('[file]%s (%d bytes)', $value->getClientOriginalName(), $value->getSize());
            }

            if (in_array($key, $sensitiveKeys, true)) {
                $value = '***redacted***';
            }
        });

        return Arr::where($payload, fn ($value) => $value !== null && $value !== '');
    }
}
