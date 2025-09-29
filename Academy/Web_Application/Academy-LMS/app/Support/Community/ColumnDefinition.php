<?php
declare(strict_types=1);


namespace App\Support\Community;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class ColumnDefinition
{
    public static function name(string $key): string
    {
        $columns = config('community.columns', []);

        if (! array_key_exists($key, $columns)) {
            throw new InvalidArgumentException("Unknown community column key [{$key}].");
        }

        return (string) $columns[$key];
    }

    public static function names(): array
    {
        return Arr::map(config('community.columns', []), static fn ($value) => (string) $value);
    }
}
