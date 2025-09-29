<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Base controller for community API endpoints providing shared helpers.
 */
abstract class CommunityApiController extends Controller
{
    protected function ok(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(['data' => $payload], $status);
    }
}
