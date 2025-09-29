<?php

declare(strict_types=1);

namespace App\Support\Http;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Central builder responsible for producing consistent JSON API envelopes.
 */
class ApiResponseBuilder
{
    public function __construct(
        private readonly string $requestId,
        private readonly string $timezone = 'UTC'
    ) {
    }

    public static function make(?string $requestId = null, ?string $timezone = null): self
    {
        return new self(
            $requestId ?? (string) Str::orderedUuid(),
            $timezone ?? config('app.timezone', 'UTC')
        );
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function success(mixed $data = null, array $meta = [], int $status = SymfonyResponse::HTTP_OK): JsonResponse
    {
        [$payload, $metaPayload] = $this->normalizeSuccessPayload($data, $meta);

        return $this->json([
            'data' => $payload,
            'meta' => $this->metaBase($status, $metaPayload),
        ], $status);
    }

    public function paginated(
        LengthAwarePaginator|PaginatorContract|AbstractCursorPaginator $paginator,
        array $meta = [],
        int $status = SymfonyResponse::HTTP_OK
    ): JsonResponse {
        [$items, $pagination] = $this->extractPaginationMeta($paginator);

        $meta['pagination'] = array_merge($meta['pagination'] ?? [], $pagination);

        return $this->success($items, $meta, $status);
    }

    public function noContent(): JsonResponse
    {
        $response = new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        $response->headers->set('X-Request-Id', $this->requestId);

        return $response;
    }

    public function error(
        string $title,
        ?string $detail = null,
        int $status = SymfonyResponse::HTTP_BAD_REQUEST,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $entries = $this->normalizeErrors($errors, $status, $title, $detail);

        if ($entries === []) {
            $entries[] = [
                'title' => $title,
                'detail' => $detail ?? $title,
                'status' => (string) $status,
                'code' => $this->defaultCodeForStatus($status),
            ];
        }

        return $this->json([
            'errors' => $entries,
            'meta' => $this->metaBase($status, $meta),
        ], $status);
    }

    private function normalizeSuccessPayload(mixed $data, array $meta): array
    {
        if ($data instanceof LengthAwarePaginator || $data instanceof PaginatorContract || $data instanceof AbstractCursorPaginator) {
            [$items, $pagination] = $this->extractPaginationMeta($data);
            $meta['pagination'] = array_merge($meta['pagination'] ?? [], $pagination);

            return [$items, $meta];
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        if (is_object($data) && method_exists($data, 'toArray')) {
            /** @phpstan-ignore-next-line */
            $data = $data->toArray();
        }

        return [$data, $meta];
    }

    /**
     * @return array{0: array<int, mixed>|array<string, mixed>, 1: array<string, mixed>}
     */
    private function extractPaginationMeta(LengthAwarePaginator|PaginatorContract|AbstractCursorPaginator $paginator): array
    {
        if ($paginator instanceof AbstractCursorPaginator) {
            $items = $paginator->items();
            $nextCursor = $paginator->nextCursor();
            $previousCursor = $paginator->previousCursor();

            return [
                $items,
                array_filter([
                    'type' => 'cursor',
                    'per_page' => $paginator->perPage(),
                    'has_more' => $paginator->hasMorePages(),
                    'next_cursor' => $nextCursor?->encode(),
                    'previous_cursor' => $previousCursor?->encode(),
                    'count' => count($items),
                ], static fn ($value) => $value !== null),
            ];
        }

        if ($paginator instanceof LengthAwarePaginator) {
            $items = $paginator->items();

            return [
                $items,
                array_filter([
                    'type' => 'page',
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'has_more' => $paginator->hasMorePages(),
                    'next_cursor' => null,
                    'previous_cursor' => null,
                    'count' => count($items),
                    'links' => [
                        'first' => $paginator->url(1),
                        'last' => $paginator->url($paginator->lastPage()),
                        'next' => $paginator->nextPageUrl(),
                        'prev' => $paginator->previousPageUrl(),
                    ],
                ], static fn ($value) => $value !== null),
            ];
        }

        $items = $paginator->items();

        return [
            $items,
            array_filter([
                'type' => 'page',
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
                'next_cursor' => null,
                'previous_cursor' => null,
                'count' => count($items),
                'links' => [
                    'next' => $paginator->nextPageUrl(),
                    'prev' => $paginator->previousPageUrl(),
                ],
            ], static fn ($value) => $value !== null),
        ];
    }

    private function normalizeErrors(array $errors, int $status, string $title, ?string $detail): array
    {
        if ($errors === []) {
            return [];
        }

        if ($this->isListOfErrorEntries($errors)) {
            return array_map(function (array $error) use ($status, $title, $detail) {
                $error['title'] ??= $title;
                $error['detail'] ??= $detail ?? $title;
                $error['status'] = (string) ($error['status'] ?? $status);
                $error['code'] ??= $this->defaultCodeForStatus((int) $error['status']);

                return $error;
            }, $errors);
        }

        $normalized = [];

        foreach ($errors as $field => $messages) {
            foreach (Arr::wrap($messages) as $message) {
                $normalized[] = [
                    'title' => $title,
                    'detail' => (string) $message,
                    'code' => $this->validationCode((string) $field),
                    'status' => (string) $status,
                    'source' => [
                        'parameter' => (string) $field,
                        'pointer' => $this->pointerForField((string) $field),
                    ],
                ];
            }
        }

        return $normalized;
    }

    private function metaBase(int $status, array $meta): array
    {
        $meta['status'] = $status;
        $meta['request_id'] = $this->requestId;
        $meta['timestamp'] = CarbonImmutable::now($this->timezone)->toIso8601String();

        return $meta;
    }

    private function json(array $payload, int $status): JsonResponse
    {
        $response = new JsonResponse(
            $payload,
            $status,
            [],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $response->headers->set('X-Request-Id', $this->requestId);

        return $response;
    }

    private function defaultCodeForStatus(int $status): string
    {
        return 'http_' . $status;
    }

    private function validationCode(string $field): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $field) ?? 'field';
        $normalized = trim($normalized, '_');

        return 'validation.' . ($normalized !== '' ? $normalized : 'field');
    }

    private function pointerForField(string $field): string
    {
        $segments = preg_split('/\.+/', $field) ?: [];
        $segments = array_map(static function (string $segment) {
            return trim(str_replace(['[', ']'], ['.', ''], $segment), '.');
        }, $segments);
        $segments = array_values(array_filter($segments, static fn ($segment) => $segment !== ''));

        return '/data/attributes/' . implode('/', $segments);
    }

    private function isListOfErrorEntries(array $errors): bool
    {
        if (! function_exists('array_is_list')) {
            return $this->legacyArrayIsList($errors);
        }

        return array_is_list($errors) && (empty($errors) || is_array($errors[0] ?? null));
    }

    private function legacyArrayIsList(array $array): bool
    {
        $expectedKey = 0;
        foreach ($array as $key => $_) {
            if ($key !== $expectedKey++) {
                return false;
            }
        }

        return true;
    }
}
