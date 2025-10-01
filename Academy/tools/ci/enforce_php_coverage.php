<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php enforce_php_coverage.php <clover-file> <min-percentage> <summary-output>\n");
    exit(1);
}

[$script, $cloverPath, $minPercentage, $summaryOutput] = $argv;

if (!is_file($cloverPath)) {
    fwrite(STDERR, sprintf('Coverage file "%s" was not found.%s', $cloverPath, PHP_EOL));
    exit(1);
}

$min = (float) $minPercentage;
if ($min <= 0 || $min > 100) {
    fwrite(STDERR, 'Minimum coverage must be between 0 and 100.' . PHP_EOL);
    exit(1);
}

$xml = new SimpleXMLElement(file_get_contents($cloverPath));
if (!isset($xml->project->metrics)) {
    fwrite(STDERR, 'Unable to locate metrics node in Clover report.' . PHP_EOL);
    exit(1);
}

$metrics = $xml->project->metrics;
$totalStatements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];
$totalMethods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];
$totalConditionals = (int) $metrics['conditionals'];
$coveredConditionals = (int) $metrics['coveredconditionals'];

$lineCoverage = $totalStatements > 0 ? ($coveredStatements / $totalStatements) * 100 : 0.0;
$methodCoverage = $totalMethods > 0 ? ($coveredMethods / $totalMethods) * 100 : 0.0;
$branchCoverage = $totalConditionals > 0 ? ($coveredConditionals / $totalConditionals) * 100 : 0.0;

$summary = [
    'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM),
    'thresholds' => [
        'line' => $min,
        'method' => max($min - 5, 0),
        'branch' => max($min - 10, 0),
    ],
    'coverage' => [
        'line' => round($lineCoverage, 2),
        'method' => round($methodCoverage, 2),
        'branch' => round($branchCoverage, 2),
        'covered_statements' => $coveredStatements,
        'total_statements' => $totalStatements,
        'covered_methods' => $coveredMethods,
        'total_methods' => $totalMethods,
        'covered_conditionals' => $coveredConditionals,
        'total_conditionals' => $totalConditionals,
    ],
];

$summaryDir = dirname($summaryOutput);
if (!is_dir($summaryDir) && !mkdir($summaryDir, 0775, true) && !is_dir($summaryDir)) {
    fwrite(STDERR, sprintf('Unable to create directory for summary output "%s".%s', $summaryDir, PHP_EOL));
    exit(1);
}

file_put_contents($summaryOutput, json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

$lineOk = $summary['coverage']['line'] >= $summary['thresholds']['line'];
$methodOk = $summary['coverage']['method'] >= $summary['thresholds']['method'];
$branchOk = $summary['coverage']['branch'] >= $summary['thresholds']['branch'];

if ($lineOk && $methodOk && $branchOk) {
    fwrite(STDOUT, sprintf(
        "PHPUnit coverage OK: lines %.2f%% (min %.2f%%), methods %.2f%% (min %.2f%%), branches %.2f%% (min %.2f%%).%s",
        $summary['coverage']['line'],
        $summary['thresholds']['line'],
        $summary['coverage']['method'],
        $summary['thresholds']['method'],
        $summary['coverage']['branch'],
        $summary['thresholds']['branch'],
        PHP_EOL
    ));
    exit(0);
}

$failures = [];
if (!$lineOk) {
    $failures[] = sprintf('line coverage %.2f%% below threshold %.2f%%', $summary['coverage']['line'], $summary['thresholds']['line']);
}
if (!$methodOk) {
    $failures[] = sprintf('method coverage %.2f%% below threshold %.2f%%', $summary['coverage']['method'], $summary['thresholds']['method']);
}
if (!$branchOk) {
    $failures[] = sprintf('branch coverage %.2f%% below threshold %.2f%%', $summary['coverage']['branch'], $summary['thresholds']['branch']);
}

fwrite(STDERR, 'Coverage requirements not met: ' . implode('; ', $failures) . PHP_EOL);
exit(2);
