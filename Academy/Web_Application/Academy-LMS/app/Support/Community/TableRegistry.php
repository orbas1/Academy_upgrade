<?php
declare(strict_types=1);


namespace App\Support\Community;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class TableRegistry
{
    /**
     * Return the configured table name for the provided key.
     */
    public static function get(string $key): string
    {
        $tables = static::all();
        if (! array_key_exists($key, $tables)) {
            throw new InvalidArgumentException("Unknown community table key [{$key}].");
        }

        return $tables[$key];
    }

    /**
     * Return the configured table names map.
     */
    public static function all(): array
    {
        $tables = config('community.tables', []);

        return Arr::map($tables, static function ($name) {
            return (string) $name;
        });
    }

    /**
     * Determine if the provided table name follows snake_case plural convention.
     */
    public static function assertConvention(string $table): void
    {
        if (! preg_match('/^[a-z]+(_[a-z]+)*s$/', $table)) {
            throw new InvalidArgumentException("Community table [{$table}] must be snake_case plural.");
        }
    }
}
