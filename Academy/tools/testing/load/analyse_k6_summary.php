#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php analyse_k6_summary.php <summary.json> [--markdown=path]\n");
    exit(1);
}

$summaryPath = $argv[1];
$markdownPath = null;

foreach (array_slice($argv, 2) as $argument) {
    if (str_starts_with($argument, '--markdown=')) {
        $markdownPath = substr($argument, 11);
    }
}

if (! is_file($summaryPath)) {
    fwrite(STDERR, sprintf("Summary file not found: %s\n", $summaryPath));
    exit(1);
}

$data = json_decode((string) file_get_contents($summaryPath), true, flags: JSON_THROW_ON_ERROR);

$metrics = $data['metrics'] ?? [];
$scenarios = $data['state'] ?? [];

$durationValues = $metrics['profile_activity_duration']['values'] ?? [];
$requestsValues = $metrics['profile_activity_requests']['values'] ?? [];
$errorsValues = $metrics['profile_activity_errors']['values'] ?? [];
$httpFailures = $metrics['http_req_failed']['values'] ?? [];

$requestsCount = (int) ($requestsValues['count'] ?? 0);
$failureRate = (float) ($errorsValues['rate'] ?? 0.0);
$httpFailureRate = (float) ($httpFailures['rate'] ?? 0.0);
$vusMax = (int) ($scenarios['profile_activity']['max_vus'] ?? 0);

$avgLatency = $durationValues['avg'] ?? 0.0;
$p95Latency = $durationValues['p(95)'] ?? 0.0;
$maxLatency = $durationValues['max'] ?? 0.0;

$summaryTable = [
    ['Metric', 'Value'],
    ['Total requests', number_format($requestsCount)],
    ['Average latency (ms)', number_format($avgLatency, 2)],
    ['p95 latency (ms)', number_format($p95Latency, 2)],
    ['Max latency (ms)', number_format($maxLatency, 2)],
    ['Request failure rate', number_format($failureRate * 100, 2) . '%'],
    ['HTTP failure rate', number_format($httpFailureRate * 100, 2) . '%'],
    ['Max VUs observed', number_format($vusMax)],
];

$markdown = [
    '# Load Test Summary',
    '',
    sprintf('*Source*: `%s`', basename($summaryPath)),
    '',
    '| Metric | Value |',
    '| --- | --- |',
];

foreach (array_slice($summaryTable, 1) as $row) {
    $markdown[] = sprintf('| %s | %s |', $row[0], $row[1]);
}

$thresholds = $data['thresholds'] ?? [];
if ($thresholds !== []) {
    $markdown[] = '';
    $markdown[] = '## Thresholds';
    $markdown[] = '';
    foreach ($thresholds as $name => $result) {
        $status = $result['ok'] ?? false ? 'pass' : 'fail';
        $markdown[] = sprintf('- **%s**: %s', $name, $status);
    }
}

$markdownOutput = implode("\n", $markdown) . "\n";

echo $markdownOutput;

if ($markdownPath) {
    $directory = dirname($markdownPath);
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents($markdownPath, $markdownOutput);
}
