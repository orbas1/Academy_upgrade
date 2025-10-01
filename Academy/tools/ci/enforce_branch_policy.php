<?php

declare(strict_types=1);

$eventName = getenv('GITHUB_EVENT_NAME') ?: '';
if ($eventName !== 'pull_request') {
    fwrite(STDOUT, sprintf("Branch policy enforcement skipped for event '%s'.%s", $eventName, PHP_EOL));
    exit(0);
}

$branch = getenv('GITHUB_HEAD_REF') ?: '';
if ($branch === '') {
    fwrite(STDERR, "Unable to resolve pull request head branch name." . PHP_EOL);
    exit(1);
}

$allowedExact = ['develop'];
$allowedPrefixes = [
    'feature/',
    'bugfix/',
    'hotfix/',
    'chore/',
    'task/',
    'refactor/',
    'release/',
];

$isAllowed = in_array($branch, $allowedExact, true);

if (!$isAllowed) {
    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with($branch, $prefix)) {
            $isAllowed = true;
            break;
        }
    }
}

if ($isAllowed) {
    fwrite(STDOUT, sprintf('Branch "%s" complies with policy requirements.%s', $branch, PHP_EOL));
    exit(0);
}

$allowedSummary = array_merge($allowedExact, array_map(static fn ($prefix) => $prefix . '*', $allowedPrefixes));
fwrite(
    STDERR,
    sprintf(
        'Branch "%s" violates policy. Allowed naming: %s%s',
        $branch,
        implode(', ', $allowedSummary),
        PHP_EOL
    )
);
exit(2);
