<?php

declare(strict_types=1);

/**
 * Dependency-free test runner.
 *
 * PHPUnit is the project's test framework (see phpunit.xml), but the engine is
 * the build gate for a product that ships to a plain PHP host, so the same
 * sourced cases must also be runnable with nothing installed:
 *
 *     php bin/run-tests.php
 *     php bin/run-tests.php --group=awl
 *     php bin/run-tests.php --verbose
 */

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../tests/Support/TestCaseDefinition.php';
require __DIR__ . '/../tests/Support/CaseRegistry.php';
require __DIR__ . '/../tests/Support/CaseRunner.php';

use Faraid\Tests\Support\CaseRegistry;
use Faraid\Tests\Support\CaseRunner;

$options = getopt('', ['group::', 'verbose', 'id::']);
$groupFilter = $options['group'] ?? null;
$idFilter = $options['id'] ?? null;
$verbose = array_key_exists('verbose', $options);

$cases = CaseRegistry::all();

$passed = 0;
$failed = 0;
$skipped = 0;
$unverified = 0;
$failures = [];
$byGroup = [];

foreach ($cases as $case) {
    if ($groupFilter !== null && $case->group !== $groupFilter) {
        $skipped++;
        continue;
    }
    if ($idFilter !== null && $case->id !== $idFilter) {
        $skipped++;
        continue;
    }

    if ($case->verifiedBy === null) {
        $unverified++;
    }

    $problems = CaseRunner::run($case);
    $byGroup[$case->group] ??= ['pass' => 0, 'fail' => 0];

    if ($problems === []) {
        $passed++;
        $byGroup[$case->group]['pass']++;
        if ($verbose) {
            printf("  ok   %-52s %s\n", $case->id, $case->title);
        }

        continue;
    }

    $failed++;
    $byGroup[$case->group]['fail']++;
    $failures[] = [$case, $problems];
    printf("  FAIL %-52s %s\n", $case->id, $case->title);
    foreach ($problems as $problem) {
        printf("       - %s\n", $problem);
    }
}

echo "\n";
foreach ($byGroup as $group => $counts) {
    printf("  %-16s %2d passed, %d failed\n", $group, $counts['pass'], $counts['fail']);
}

printf("\n  %d cases: %d passed, %d failed", $passed + $failed, $passed, $failed);
if ($skipped > 0) {
    printf(", %d filtered out", $skipped);
}
echo "\n";

// The suite is only a build gate once a qualified scholar has signed off the
// expected answers. Until then, say so on every run rather than letting a row
// of green ticks imply an authority the project does not yet have.
if ($unverified > 0) {
    printf(
        "\n  WARNING: %d of %d executed cases carry no scholar sign-off.\n"
        . "  Expected answers are a developer's reading of the cited rules.\n"
        . "  This engine is NOT cleared for public use. See docs/REVIEW-CHECKLIST.md.\n",
        $unverified,
        $passed + $failed
    );
}

exit($failed === 0 ? 0 : 1);
