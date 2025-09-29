<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Support\Http\ApiResponseBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait InteractsWithApiResponses
{
    protected function apiResponse(): ApiResponseBuilder
    {
        return app(ApiResponseBuilder::class);
    }

    protected function respondWithData(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return $this->apiResponse()->success($data, $meta, $status);
    }

    protected function respondWithPagination(
        LengthAwarePaginator|Paginator|AbstractCursorPaginator $paginator,
        array $meta = [],
        int $status = 200
    ): JsonResponse {
        return $this->apiResponse()->paginated($paginator, $meta, $status);
    }

    protected function respondNoContent(): JsonResponse
    {
        return $this->apiResponse()->noContent();
    }

    protected function respondWithError(
        string $title,
        ?string $detail = null,
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return $this->apiResponse()->error($title, $detail, $status, $errors, $meta);
    }
}
