<?php

declare(strict_types=1);

/**
 * Command-line front end, for checking a case by hand without a web server.
 *
 *     php bin/calculate.php '{"madhhab":"hanafi","deceased_gender":"male","heirs":{"wives":1,"sons":2,"daughters":3}}'
 *     echo '{...}' | php bin/calculate.php
 *     php bin/calculate.php --json '{...}'      # raw engine output
 */

require __DIR__ . '/../src/autoload.php';

use Faraid\Calculator;

$argv = $_SERVER['argv'];
array_shift($argv);

$asJson = false;
$payload = null;
foreach ($argv as $argument) {
    if ($argument === '--json') {
        $asJson = true;
        continue;
    }
    $payload = $argument;
}

if ($payload === null) {
    $payload = stream_get_contents(STDIN) ?: '';
}

$input = json_decode(trim($payload), true);
if (!is_array($input)) {
    fwrite(STDERR, "Expected a JSON object describing the estate and heirs.\n");
    exit(2);
}

try {
    $result = (new Calculator())->calculate($input);
} catch (\Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$output = $result->toArray();

if ($asJson) {
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
    exit(0);
}

printf("Madhhab: %s", $output['madhhab']);
if ($output['special_case'] !== null) {
    printf("   Case: %s", $output['special_case']);
}
printf("   Denominator: %d\n", $output['denominator']);
if ($output['net_estate'] !== null) {
    printf("Net estate: %s\n", number_format($output['net_estate'], 2));
}
echo str_repeat('-', 72), "\n";

foreach ($output['shares'] as $share) {
    printf(
        "%-28s %2d  %8s  %7s%%  %s\n",
        $share['heir'] . (isset($share['via']) ? ' (' . $share['via'] . ')' : ''),
        $share['count'],
        $share['numerator'] . '/' . $share['denominator'],
        rtrim(rtrim(number_format($share['percent'], 4, '.', ''), '0'), '.'),
        $share['amount'] ?? ''
    );
}

if ($output['excluded'] !== []) {
    echo "\nExcluded:\n";
    foreach ($output['excluded'] as $exclusion) {
        printf("  %-28s %s\n", $exclusion['heir'], $exclusion['reason_key']);
    }
}

if ($output['warnings'] !== []) {
    echo "\nNotes:\n";
    foreach ($output['warnings'] as $warning) {
        echo "  - $warning\n";
    }
}

echo "\nThis is an educational calculation from an engine that has not yet been\n";
echo "reviewed by a qualified scholar. Confirm with a Mufti before acting on it.\n";
