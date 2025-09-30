<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithApiResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class AdminApiController extends Controller
{
    use InteractsWithApiResponses;

    protected function ok(mixed $data = null, array $meta = [], int $status = SymfonyResponse::HTTP_OK): JsonResponse
    {
        if ($status === SymfonyResponse::HTTP_NO_CONTENT) {
            return $this->respondNoContent();
        }

        return $this->respondWithData($data, $meta, $status);
    }

    protected function paginated(
        \Illuminate\Pagination\CursorPaginator|\Illuminate\Pagination\LengthAwarePaginator $paginator,
        array $meta = []
    ): JsonResponse {
        return $this->respondWithPagination($paginator, $meta);
    }
}
