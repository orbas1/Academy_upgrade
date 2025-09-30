<?php

namespace App\Services\Systemd;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EnvironmentFileEditor
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    /**
     * @return array<string, string>
     */
    public function read(string $path): array
    {
        if (! $this->filesystem->exists($path)) {
            return [];
        }

        $contents = $this->filesystem->get($path);
        $lines = preg_split('/\r?\n/', $contents) ?: [];
        $variables = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || Str::startsWith($trimmed, '#')) {
                continue;
            }

            if (! str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $variables[trim($key)] = $this->unquote(trim($value));
        }

        return $variables;
    }

    /**
     * @param  array<string, string|int>  $values
     */
    public function write(string $path, array $values): void
    {
        ksort($values);

        $directory = dirname($path);
        if (! $this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory, 0750, true);
        }

        $buffer = [
            '# Managed by Academy queue autoscaler',
            sprintf('# Last updated: %s', now()->toRfc3339String()),
        ];

        foreach ($values as $key => $value) {
            $buffer[] = sprintf('%s=%s', strtoupper($key), $this->quoteIfNeeded((string) $value));
        }

        $this->filesystem->put($path, implode(PHP_EOL, $buffer) . PHP_EOL, true);
        $this->filesystem->chmod($path, 0640);
    }

    private function unquote(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if ((Str::startsWith($value, "'") && Str::endsWith($value, "'")) || (Str::startsWith($value, '"') && Str::endsWith($value, '"'))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private function quoteIfNeeded(string $value): string
    {
        if ($value === '' || preg_match('/\s/', $value)) {
            return escapeshellarg($value);
        }

        return $value;
    }
}
