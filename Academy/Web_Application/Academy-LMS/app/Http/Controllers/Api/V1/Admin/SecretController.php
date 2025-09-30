<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Secrets\SecretManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class SecretController extends Controller
{
    public function __construct(private readonly SecretManager $secrets)
    {
    }

    public function show(Request $request, string $key): JsonResponse
    {
        Gate::authorize('secrets.manage');

        $secret = $this->secrets->get($key, $request->query('driver'));

        return response()->json([
            'data' => [
                'key' => $secret->key,
                'value' => $secret->value,
                'version' => $secret->version,
                'retrieved_at' => $secret->retrievedAt->toIso8601String(),
                'rotated_at' => $secret->rotatedAt?->toIso8601String(),
                'masked' => $secret->maskedValue(),
                'metadata' => $secret->metadata,
            ],
        ]);
    }

    public function rotate(Request $request, string $key): JsonResponse
    {
        Gate::authorize('secrets.manage');

        $result = $this->secrets->rotate($key, $request->input('driver'), [
            'value' => $request->input('value'),
        ]);

        $secret = $this->secrets->get($key, $request->input('driver'));

        return response()->json([
            'data' => [
                'key' => $key,
                'version' => $result->version,
                'rotated_at' => $result->rotatedAt->toIso8601String(),
                'value' => $secret->value,
                'masked' => $secret->maskedValue(),
                'metadata' => $secret->metadata,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
