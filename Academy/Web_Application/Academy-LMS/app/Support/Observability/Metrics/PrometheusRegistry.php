<?php

declare(strict_types=1);

namespace App\Support\Observability\Metrics;

use BadMethodCallException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use RuntimeException;

class PrometheusRegistry
{
    private readonly bool $enabled;

    private readonly string $prefix;

    private readonly int $retentionSeconds;

    private readonly int $windowRetentionSeconds;

    private readonly array $defaultBuckets;

    private readonly int $lockSeconds;

    private readonly int $lockWaitSeconds;

    private readonly bool $lockingEnabled;

    public function __construct(
        private readonly CacheRepository $store,
        array $config = []
    ) {
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->prefix = (string) ($config['prefix'] ?? 'observability');
        $this->retentionSeconds = max(60, (int) ($config['retention_seconds'] ?? 86_400));
        $this->windowRetentionSeconds = max(60, (int) ($config['window_retention_seconds'] ?? 7_200));
        $this->defaultBuckets = $this->normalizeBuckets($config['default_buckets'] ?? []);
        $this->lockSeconds = max(1, (int) ($config['lock_seconds'] ?? 5));
        $this->lockWaitSeconds = max(0, (int) ($config['lock_wait_seconds'] ?? 3));
        $this->lockingEnabled = ($config['locking'] ?? true) === true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->withLock("counter:{$name}", function () use ($name, $labels, $value): void {
            $entries = $this->store->get($this->metricKey('counter', $name), []);
            $hash = $this->hashLabels($labels);
            $now = Carbon::now();

            $entry = $entries[$hash] ?? [
                'labels' => $labels,
                'value' => 0.0,
                'window' => [],
            ];

            $entry['labels'] = $labels;
            $entry['value'] = (float) ($entry['value'] ?? 0.0) + $value;
            $entry['window'][] = [
                'timestamp' => $now->getTimestamp(),
                'value' => $value,
            ];

            $entry['window'] = $this->pruneWindow($entry['window']);

            $entries[$hash] = $entry;

            $this->store->put($this->metricKey('counter', $name), $entries, $now->addSeconds($this->retentionSeconds));
            $this->rememberMetricName('counter', $name);
        });
    }

    public function observeHistogram(string $name, float $value, array $labels = [], ?array $buckets = null): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->withLock("histogram:{$name}", function () use ($name, $value, $labels, $buckets): void {
            $metricBuckets = $this->normalizeBuckets($buckets ?? $this->defaultBuckets);
            if (empty($metricBuckets)) {
                throw new RuntimeException('Histogram metrics require at least one bucket.');
            }

            $entries = $this->store->get($this->metricKey('histogram', $name), []);
            $hash = $this->hashLabels($labels);

            $entry = $entries[$hash] ?? [
                'labels' => $labels,
                'buckets' => $metricBuckets,
                'counts' => $this->initialiseBucketCounts($metricBuckets),
                'sum' => 0.0,
                'count' => 0.0,
            ];

            $entry['labels'] = $labels;
            $entry['buckets'] = $metricBuckets;
            $entry['sum'] = (float) ($entry['sum'] ?? 0.0) + $value;
            $entry['count'] = (float) ($entry['count'] ?? 0.0) + 1.0;

            foreach ($metricBuckets as $bucket) {
                $key = $this->formatBucketKey($bucket);
                if ($value <= $bucket) {
                    $entry['counts'][$key] = (float) ($entry['counts'][$key] ?? 0.0) + 1.0;
                }
            }

            $entry['counts']['+Inf'] = (float) ($entry['counts']['+Inf'] ?? 0.0) + 1.0;

            $entries[$hash] = $entry;
            $this->store->put(
                $this->metricKey('histogram', $name),
                $entries,
                Carbon::now()->addSeconds($this->retentionSeconds)
            );
            $this->rememberMetricName('histogram', $name);
        });
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->withLock("gauge:{$name}", function () use ($name, $value, $labels): void {
            $entries = $this->store->get($this->metricKey('gauge', $name), []);
            $hash = $this->hashLabels($labels);

            $entries[$hash] = [
                'labels' => $labels,
                'value' => $value,
                'updated_at' => Carbon::now()->getTimestamp(),
            ];

            $this->store->put(
                $this->metricKey('gauge', $name),
                $entries,
                Carbon::now()->addSeconds($this->retentionSeconds)
            );
            $this->rememberMetricName('gauge', $name);
        });
    }

    public function counterWindowSum(string $name, array $labels, int $windowSeconds): float
    {
        if (! $this->enabled) {
            return 0.0;
        }

        $entries = $this->store->get($this->metricKey('counter', $name), []);
        $hash = $this->hashLabels($labels);
        if (! isset($entries[$hash])) {
            return 0.0;
        }

        $window = $this->pruneWindow($entries[$hash]['window'] ?? [], $windowSeconds);
        $sum = 0.0;
        foreach ($window as $point) {
            $sum += (float) ($point['value'] ?? 0.0);
        }

        return $sum;
    }

    public function getGaugeValue(string $name, array $labels): ?float
    {
        if (! $this->enabled) {
            return null;
        }

        $entries = $this->store->get($this->metricKey('gauge', $name), []);
        $hash = $this->hashLabels($labels);
        if (! isset($entries[$hash])) {
            return null;
        }

        return isset($entries[$hash]['value']) ? (float) $entries[$hash]['value'] : null;
    }

    public function render(): string
    {
        if (! $this->enabled) {
            return "";
        }

        $lines = [];

        foreach ($this->store->get($this->metricNamesKey('counter'), []) as $name) {
            $entries = $this->store->get($this->metricKey('counter', $name), []);
            if (empty($entries)) {
                continue;
            }

            $lines[] = sprintf('# TYPE %s counter', $name);
            foreach ($entries as $entry) {
                $labels = $entry['labels'] ?? [];
                $value = $this->formatNumber((float) ($entry['value'] ?? 0.0));
                $lines[] = sprintf('%s%s %s', $name, $this->formatLabels($labels), $value);
            }
        }

        foreach ($this->store->get($this->metricNamesKey('histogram'), []) as $name) {
            $entries = $this->store->get($this->metricKey('histogram', $name), []);
            if (empty($entries)) {
                continue;
            }

            $lines[] = sprintf('# TYPE %s histogram', $name);
            foreach ($entries as $entry) {
                $labels = $entry['labels'] ?? [];
                $buckets = $this->normalizeBuckets($entry['buckets'] ?? $this->defaultBuckets);
                $counts = $entry['counts'] ?? [];

                foreach ($buckets as $bucket) {
                    $bucketKey = $this->formatBucketKey($bucket);
                    $count = $this->formatNumber((float) ($counts[$bucketKey] ?? 0.0));
                    $lines[] = sprintf(
                        '%s_bucket%s %s',
                        $name,
                        $this->formatLabels(array_merge($labels, ['le' => $bucketKey])),
                        $count
                    );
                }

                $lines[] = sprintf(
                    '%s_bucket%s %s',
                    $name,
                    $this->formatLabels(array_merge($labels, ['le' => '+Inf'])),
                    $this->formatNumber((float) ($counts['+Inf'] ?? 0.0))
                );
                $lines[] = sprintf('%s_sum%s %s', $name, $this->formatLabels($labels), $this->formatNumber((float) ($entry['sum'] ?? 0.0)));
                $lines[] = sprintf('%s_count%s %s', $name, $this->formatLabels($labels), $this->formatNumber((float) ($entry['count'] ?? 0.0)));
            }
        }

        foreach ($this->store->get($this->metricNamesKey('gauge'), []) as $name) {
            $entries = $this->store->get($this->metricKey('gauge', $name), []);
            if (empty($entries)) {
                continue;
            }

            $lines[] = sprintf('# TYPE %s gauge', $name);
            foreach ($entries as $entry) {
                $labels = $entry['labels'] ?? [];
                $value = $this->formatNumber((float) ($entry['value'] ?? 0.0));
                $lines[] = sprintf('%s%s %s', $name, $this->formatLabels($labels), $value);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function metricKey(string $type, string $name): string
    {
        return sprintf('%s:%s:%s', $this->prefix, $type, $name);
    }

    private function metricNamesKey(string $type): string
    {
        return sprintf('%s:%s:names', $this->prefix, $type);
    }

    private function rememberMetricName(string $type, string $name): void
    {
        $this->withLock("names:{$type}", function () use ($type, $name): void {
            $key = $this->metricNamesKey($type);
            $names = $this->store->get($key, []);
            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }

            $this->store->forever($key, $names);
        });
    }

    private function hashLabels(array $labels): string
    {
        ksort($labels);

        return sha1(json_encode($labels, JSON_THROW_ON_ERROR));
    }

    private function normalizeBuckets(array $buckets): array
    {
        $normalized = [];
        foreach ($buckets as $bucket) {
            $normalized[] = (float) $bucket;
        }

        sort($normalized);

        return array_values(array_unique($normalized, SORT_NUMERIC));
    }

    private function initialiseBucketCounts(array $buckets): array
    {
        $counts = [];
        foreach ($buckets as $bucket) {
            $counts[$this->formatBucketKey($bucket)] = 0.0;
        }

        $counts['+Inf'] = 0.0;

        return $counts;
    }

    private function pruneWindow(array $window, ?int $windowSeconds = null): array
    {
        $windowSeconds ??= $this->windowRetentionSeconds;
        $cutoff = Carbon::now()->getTimestamp() - $windowSeconds;

        return array_values(array_filter($window, static function (array $point) use ($cutoff) {
            return isset($point['timestamp']) && (int) $point['timestamp'] >= $cutoff;
        }));
    }

    private function formatLabels(array $labels): string
    {
        if ($labels === []) {
            return '';
        }

        ksort($labels);
        $parts = [];
        foreach ($labels as $key => $value) {
            $parts[] = sprintf('%s="%s"', $key, $this->escapeLabelValue((string) $value));
        }

        return '{'.implode(',', $parts).'}';
    }

    private function escapeLabelValue(string $value): string
    {
        return str_replace(['"', "\n"], ['\\"', '\n'], $value);
    }

    private function formatNumber(float $value): string
    {
        if (is_infinite($value) || is_nan($value)) {
            return '0';
        }

        $formatted = sprintf('%.10F', $value);

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function formatBucketKey(float $bucket): string
    {
        $formatted = $this->formatNumber($bucket);

        return $formatted === '' ? '0' : $formatted;
    }

    private function withLock(string $name, callable $callback): void
    {
        if (! $this->lockingEnabled) {
            $callback();

            return;
        }

        try {
            $this->store->lock($this->lockKey($name), $this->lockSeconds)
                ->block($this->lockWaitSeconds, static fn () => $callback());
        } catch (BadMethodCallException) {
            $callback();
        }
    }

    private function lockKey(string $name): string
    {
        return sprintf('%s:lock:%s', $this->prefix, $name);
    }
}
