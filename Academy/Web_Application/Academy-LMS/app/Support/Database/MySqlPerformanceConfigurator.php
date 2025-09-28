<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MySqlPerformanceConfigurator
{
    public static function applyFromConfig(): void
    {
        $variables = config('database_performance.mysql.session_variables', []);

        if (empty($variables) || !is_array($variables)) {
            return;
        }

        self::apply($variables);
    }

    public static function apply(array $variables): void
    {
        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'mysql') {
                return;
            }

            $assignments = collect($variables)
                ->map(fn ($value, $key) => sprintf('%s = %s', $key, self::formatValue($value)))
                ->filter()
                ->implode(', ');

            if ($assignments === '') {
                return;
            }

            $connection->statement('SET SESSION ' . $assignments);
        } catch (Throwable $exception) {
            Log::warning('Failed to apply MySQL session tuning.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private static function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            $flattened = collect($value)
                ->map(fn ($itemValue, $itemKey) => sprintf('%s=%s', $itemKey, (string) $itemValue))
                ->implode(',');

            return self::quoteString($flattened);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $stringValue = (string) $value;
        $upperValue = Str::upper($stringValue);

        if (in_array($upperValue, ['ON', 'OFF', 'DEFAULT'], true)) {
            return $upperValue;
        }

        if (Str::startsWith($stringValue, ["'", '"'])) {
            return $stringValue;
        }

        return self::quoteString($stringValue);
    }

    private static function quoteString(string $value): string
    {
        return "'" . str_replace("'", "\\'", $value) . "'";
    }
}
