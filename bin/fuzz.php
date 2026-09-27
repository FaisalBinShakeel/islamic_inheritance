<?php

declare(strict_types=1);

/**
 * Randomised sweep over heir combinations.
 *
 * It cannot tell a right answer from a wrong one — only the sourced cases can
 * do that. What it does prove is that no combination of heirs makes the engine
 * crash, produce a negative share, or lose part of the estate.
 *
 *     php bin/fuzz.php [iterations] [seed]
 */

require __DIR__ . '/../src/autoload.php';

use Faraid\Calculator;
use Faraid\Exception\InvalidInput;
use Faraid\Fraction;
use Faraid\Input;
use Faraid\Madhhab\MadhhabRules;

$iterations = (int) ($argv[1] ?? 20000);
$seed = (int) ($argv[2] ?? 20260927);
mt_srand($seed);

$heirKeys = array_values(array_filter(
    Input::heirKeys(),
    static fn (string $key) => $key !== 'husband' && $key !== 'wives'
));

$calculator = new Calculator();
$checked = 0;
$rejected = 0;
$problems = [];

for ($i = 0; $i < $iterations; $i++) {
    $gender = mt_rand(0, 1) === 1 ? 'male' : 'female';
    $heirs = [];

    $howMany = mt_rand(1, 5);
    for ($j = 0; $j < $howMany; $j++) {
        $key = $heirKeys[mt_rand(0, count($heirKeys) - 1)];
        $heirs[$key] = mt_rand(1, 3);
    }
    if (mt_rand(0, 2) === 0) {
        $heirs[$gender === 'male' ? 'wives' : 'husband'] = $gender === 'male' ? mt_rand(1, 4) : 1;
    }

    $madhhabKeys = MadhhabRules::keys();
    $input = [
        'madhhab' => $madhhabKeys[mt_rand(0, count($madhhabKeys) - 1)],
        'deceased_gender' => $gender,
        'heirs' => $heirs,
    ];

    try {
        $result = $calculator->calculate($input);
    } catch (InvalidInput) {
        $rejected++;
        continue;
    } catch (\Throwable $e) {
        $problems[] = sprintf('%s: %s | %s', $e::class, $e->getMessage(), json_encode($input));
        continue;
    }

    $checked++;

    foreach ($result->shares as $share) {
        if ($share->share->isNegative()) {
            $problems[] = sprintf('negative share for %s | %s', $share->heir->value, json_encode($input));
        }
        if ($share->share->isZero()) {
            $problems[] = sprintf('zero share recorded for %s | %s', $share->heir->value, json_encode($input));
        }
    }

    if ($result->undistributed->isNegative()) {
        $problems[] = sprintf('negative remainder | %s', json_encode($input));
    }

    $total = $result->distributed->add($result->undistributed);
    if (!$total->equals(Fraction::one())) {
        $problems[] = sprintf('total %s | %s', (string) $total, json_encode($input));
    }
}

printf("%d combinations calculated, %d rejected as invalid input\n", $checked, $rejected);
if ($problems === []) {
    echo "no problems found\n";
    exit(0);
}

printf("%d problems:\n", count($problems));
foreach (array_slice($problems, 0, 25) as $problem) {
    echo "  - $problem\n";
}
exit(1);
