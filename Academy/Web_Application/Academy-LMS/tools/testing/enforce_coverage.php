#!/usr/bin/env php
<?php
$cloverPath = $argv[1] ?? __DIR__ . '/../../Web_Application/Academy-LMS/storage/logs/coverage.xml';
$lineThreshold = (float) ($argv[2] ?? 70);
$functionThreshold = (float) ($argv[3] ?? 75);
$classThreshold = (float) ($argv[4] ?? 80);

if (! file_exists($cloverPath)) {
    fwrite(STDOUT, "Coverage file not found at {$cloverPath}. Skipping enforcement.\n");
    exit(0);
}

$xml = @simplexml_load_file($cloverPath);
if (! $xml) {
    fwrite(STDERR, "Unable to parse Clover report at {$cloverPath}.\n");
    exit(1);
}

$metrics = $xml->xpath('/coverage/project/metrics');
if (! $metrics || ! isset($metrics[0])) {
    fwrite(STDERR, "Clover metrics node missing.\n");
    exit(1);
}

$metrics = $metrics[0];

$linesTotal = max(1, (int) $metrics['statements']);
$linesCovered = (int) $metrics['coveredstatements'];
$lineRate = $linesCovered / $linesTotal * 100;

$functionsTotal = max(1, (int) $metrics['methods']);
$functionsCovered = (int) $metrics['coveredmethods'];
$functionRate = $functionsCovered / $functionsTotal * 100;

$classesTotal = max(1, (int) $metrics['classes']);
$classesCovered = (int) $metrics['coveredclasses'];
$classRate = $classesCovered / $classesTotal * 100;

$failures = [];
if ($lineRate < $lineThreshold) {
    $failures[] = sprintf('Line coverage %.2f%% below threshold %.2f%%', $lineRate, $lineThreshold);
}
if ($functionRate < $functionThreshold) {
    $failures[] = sprintf('Function coverage %.2f%% below threshold %.2f%%', $functionRate, $functionThreshold);
}
if ($classRate < $classThreshold) {
    $failures[] = sprintf('Class coverage %.2f%% below threshold %.2f%%', $classRate, $classThreshold);
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }

    exit(1);
}

fwrite(STDOUT, sprintf(
    "Coverage ok: lines %.2f%%, functions %.2f%%, classes %.2f%%.\n",
    $lineRate,
    $functionRate,
    $classRate
));
