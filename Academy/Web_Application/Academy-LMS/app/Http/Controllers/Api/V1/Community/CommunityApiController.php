<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithApiResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Base controller for community API endpoints providing shared helpers.
 */
abstract class CommunityApiController extends Controller
{
    use InteractsWithApiResponses;

    protected function ok(array $payload = [], int $status = SymfonyResponse::HTTP_OK, array $meta = []): JsonResponse
    {
        if ($status === SymfonyResponse::HTTP_NO_CONTENT) {
            return $this->respondNoContent();
        }

        return $this->respondWithData($payload, $meta, $status);
    }

    protected function created(array $payload = [], array $meta = []): JsonResponse
    {
        return $this->respondWithData($payload, $meta, SymfonyResponse::HTTP_CREATED);
    }
}
