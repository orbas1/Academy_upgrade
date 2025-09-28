<?php

namespace App\Support\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\CursorPaginator;

class KeysetPaginator
{
    public static function register(): void
    {
        $resolver = fn (?int $perPage) => self::resolvePerPage($perPage);
        $defaultColumn = config('database_performance.keyset.default_order_column', 'id');
        $defaultDirection = config('database_performance.keyset.default_order_direction', 'desc');
        $cursorName = config('database_performance.keyset.cursor_name', 'cursor');

        $macro = function (?int $perPage = null, ?string $column = null, string $direction = 'desc', ?string $customCursorName = null) use ($resolver, $defaultColumn, $defaultDirection, $cursorName): CursorPaginator {
            /** @var EloquentBuilder|QueryBuilder $this */
            $column ??= $defaultColumn;
            $direction = self::normalizeDirection($direction ?: $defaultDirection);
            $cursorKey = $customCursorName ?? $cursorName;

            $this->reorder($column, $direction);

            $query = $this instanceof EloquentBuilder ? $this->getQuery() : $this;
            $orders = $query->orders ?? [];

            $hasIdOrder = collect($orders)->contains(fn ($order) => ($order['column'] ?? null) === 'id');

            if ($column !== 'id' && !$hasIdOrder) {
                $this->orderBy('id', $direction);
            }

            return $this->cursorPaginate($resolver($perPage), ['*'], $cursorKey)->withQueryString();
        };

        EloquentBuilder::macro('keysetPaginate', $macro);
        QueryBuilder::macro('keysetPaginate', $macro);
    }

    private static function resolvePerPage(?int $perPage): int
    {
        $default = (int) config('database_performance.keyset.default_per_page', 20);
        $max = (int) config('database_performance.keyset.max_per_page', 100);

        if ($perPage !== null) {
            return max(1, min($perPage, $max));
        }

        $parameter = config('database_performance.keyset.page_parameter', 'per_page');
        $requested = $default;

        if (app()->bound('request')) {
            $requested = (int) request()->integer($parameter, $default);
        }

        return max(1, min($requested, $max));
    }

    private static function normalizeDirection(?string $direction): string
    {
        return strtolower($direction ?? 'desc') === 'asc' ? 'asc' : 'desc';
    }
}
