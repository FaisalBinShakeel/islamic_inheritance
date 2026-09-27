<?php

declare(strict_types=1);

namespace Faraid\Tests\Support;

use Faraid\Calculator;
use Faraid\Fraction;
use Faraid\Result;

/**
 * Runs one case and reports every way in which the engine disagreed with the
 * expected answer. Returns all failures rather than the first, so a broken
 * rule shows its whole blast radius in one run.
 */
final class CaseRunner
{
    /** @return list<string> empty when the case passes */
    public static function run(TestCaseDefinition $case): array
    {
        $calculator = new Calculator();

        if ($case->expectError !== null) {
            try {
                $calculator->calculate($case->input);
            } catch (\Throwable $e) {
                return str_contains($e->getMessage(), $case->expectError)
                    ? []
                    : [sprintf('expected error containing "%s", got "%s"', $case->expectError, $e->getMessage())];
            }

            return [sprintf('expected an error containing "%s", but the calculation succeeded', $case->expectError)];
        }

        try {
            $result = $calculator->calculate($case->input);
        } catch (\Throwable $e) {
            return [sprintf('%s: %s', $e::class, $e->getMessage())];
        }

        return self::compare($case, $result);
    }

    /** @return list<string> */
    private static function compare(TestCaseDefinition $case, Result $result): array
    {
        $failures = [];
        $expect = $case->expect;

        if (isset($expect['shares'])) {
            $actual = [];
            foreach ($result->shares as $share) {
                $key = $share->via === null ? $share->heir->value : $share->heir->value . '@' . $share->via;
                $actual[$key] = $share->share;
            }

            foreach ($expect['shares'] as $heir => $fraction) {
                $wanted = TestCaseDefinition::parseFraction((string) $fraction);
                if (!isset($actual[$heir])) {
                    $failures[] = sprintf('%s expected %s but took nothing', $heir, (string) $wanted);
                    continue;
                }
                if (!$actual[$heir]->equals($wanted)) {
                    $failures[] = sprintf('%s expected %s, got %s', $heir, (string) $wanted, (string) $actual[$heir]);
                }
                unset($actual[$heir]);
            }

            foreach ($actual as $heir => $fraction) {
                $failures[] = sprintf('%s took %s but was not expected to inherit', $heir, (string) $fraction);
            }
        }

        if (isset($expect['per_head'])) {
            foreach ($expect['per_head'] as $heir => $fraction) {
                $share = null;
                foreach ($result->shares as $candidate) {
                    if ($candidate->heir->value === $heir) {
                        $share = $candidate;
                        break;
                    }
                }
                if ($share === null) {
                    $failures[] = sprintf('%s expected a per-head share but took nothing', $heir);
                    continue;
                }
                $wanted = TestCaseDefinition::parseFraction((string) $fraction);
                if (!$share->perHead()->equals($wanted)) {
                    $failures[] = sprintf(
                        '%s per head expected %s, got %s',
                        $heir,
                        (string) $wanted,
                        (string) $share->perHead()
                    );
                }
            }
        }

        if (isset($expect['excluded'])) {
            $actualExclusions = [];
            foreach ($result->excluded as $exclusion) {
                $actualExclusions[$exclusion->heir->value] = $exclusion->reasonKey;
            }
            foreach ($expect['excluded'] as $heir => $reason) {
                if (!isset($actualExclusions[$heir])) {
                    $failures[] = sprintf('%s was expected to be excluded, but was not', $heir);
                    continue;
                }
                if ($reason !== true && $actualExclusions[$heir] !== $reason) {
                    $failures[] = sprintf(
                        '%s excluded for "%s", expected "%s"',
                        $heir,
                        $actualExclusions[$heir],
                        (string) $reason
                    );
                }
            }
        }

        $checks = [
            'denominator' => fn () => $result->denominator(),
            'awl' => fn () => $result->awlApplied,
            'radd' => fn () => $result->raddApplied,
            'mflo' => fn () => $result->mfloApplied,
            'special_case' => fn () => $result->specialCase,
            'net_estate' => fn () => $result->netEstate,
        ];
        foreach ($checks as $key => $reader) {
            if (!array_key_exists($key, $expect)) {
                continue;
            }
            $actual = $reader();
            if ($actual !== $expect[$key]) {
                $failures[] = sprintf(
                    '%s expected %s, got %s',
                    $key,
                    var_export($expect[$key], true),
                    var_export($actual, true)
                );
            }
        }

        $expectedUndistributed = TestCaseDefinition::parseFraction((string) ($expect['undistributed'] ?? '0'));
        if (!$result->undistributed->equals($expectedUndistributed)) {
            $failures[] = sprintf(
                'undistributed expected %s, got %s',
                (string) $expectedUndistributed,
                (string) $result->undistributed
            );
        }

        foreach ($expect['warnings'] ?? [] as $warning) {
            if (!in_array($warning, $result->warnings, true)) {
                $failures[] = sprintf('expected warning "%s" was not raised', $warning);
            }
        }
        foreach ($expect['not_warnings'] ?? [] as $warning) {
            if (in_array($warning, $result->warnings, true)) {
                $failures[] = sprintf('warning "%s" should not have been raised', $warning);
            }
        }

        if (isset($expect['amounts'])) {
            foreach ($expect['amounts'] as $heir => $amount) {
                $row = null;
                foreach ($result->toArray()['shares'] as $candidate) {
                    if ($candidate['heir'] === $heir) {
                        $row = $candidate;
                        break;
                    }
                }
                if ($row === null || !isset($row['amount'])) {
                    $failures[] = sprintf('%s expected an amount but none was produced', $heir);
                    continue;
                }
                if (abs($row['amount'] - (float) $amount) > 0.005) {
                    $failures[] = sprintf('%s amount expected %s, got %s', $heir, $amount, $row['amount']);
                }
            }
        }

        // Every case, without exception: the shares must account for the whole
        // estate once the reported remainder is added back.
        $total = $result->distributed->add($result->undistributed);
        if (!$total->equals(Fraction::one())) {
            $failures[] = sprintf('shares sum to %s, not 1', (string) $total);
        }

        return $failures;
    }
}
