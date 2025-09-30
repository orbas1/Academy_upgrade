<?php

declare(strict_types=1);

namespace App\Support\Observability\Metrics;

use Illuminate\Support\Str;
use Throwable;

class StatsdClient
{
    private bool $enabled;

    public function __construct(
        private readonly MetricTransport $transport,
        private readonly string $prefix = '',
        bool $enabled = true,
        private array $defaultTags = []
    ) {
        $this->enabled = $enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function timing(string $metric, float $durationMs, array $tags = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $value = number_format($durationMs, 4, '.', '');
        $this->send($this->formatMetric($metric), sprintf('%s|ms', $value), $tags);
    }

    public function increment(string $metric, array $tags = [], int $value = 1): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->send($this->formatMetric($metric), sprintf('%d|c', $value), $tags);
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $formatted = number_format($value, 4, '.', '');
        $this->send($this->formatMetric($metric), sprintf('%s|g', $formatted), $tags);
    }

    private function send(string $metric, string $payload, array $tags = []): void
    {
        $tagString = $this->formatTags($tags);

        try {
            $this->transport->send(sprintf('%s:%s%s', $metric, $payload, $tagString));
        } catch (\Throwable) {
            // Metrics must never compromise request handling. Transport failures are ignored.
        }
    }

    private function formatMetric(string $metric): string
    {
        $metric = Str::of($metric)
            ->replaceMatches('/[^A-Za-z0-9_.]/', '_')
            ->trim('_');

        $formatted = $metric->isEmpty() ? 'metric' : (string) $metric;

        if ($this->prefix === '') {
            return $formatted;
        }

        return $this->prefix . '.' . $formatted;
    }

    private function formatTags(array $tags): string
    {
        $merged = array_filter(array_merge($this->defaultTags, $tags), static fn ($value) => $value !== null && $value !== '');

        if (empty($merged)) {
            return '';
        }

        $normalised = [];
        foreach ($merged as $key => $value) {
            if (is_int($key)) {
                continue;
            }

            $normalised[] = sprintf('%s:%s', $this->sanitizeTagComponent((string) $key), $this->sanitizeTagComponent((string) $value));
        }

        sort($normalised);

        return '|#' . implode(',', $normalised);
    }

    private function sanitizeTagComponent(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[^A-Za-z0-9_.-]/', '_')
            ->trim('_')
            ->whenEmpty(fn () => 'unknown')
            ->toString();
    }
}
