<?php

declare(strict_types=1);

namespace Faraid\Tests;

use Faraid\Calculator;
use Faraid\Fraction;
use Faraid\Tests\Support\CaseRegistry;
use Faraid\Tests\Support\CaseRunner;
use Faraid\Tests\Support\TestCaseDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The build gate. Every sourced case in tests/Cases runs here, and the engine
 * ships only when all of them pass.
 */
final class EngineTest extends TestCase
{
    #[DataProvider('cases')]
    public function testCaseProducesTheExpectedShares(TestCaseDefinition $case): void
    {
        $failures = CaseRunner::run($case);

        self::assertSame(
            [],
            $failures,
            sprintf("%s (%s)\n  %s\n  source: %s", $case->title, $case->id, implode("\n  ", $failures), $case->source)
        );
    }

    #[DataProvider('cases')]
    public function testCaseCitesItsSource(TestCaseDefinition $case): void
    {
        // A test with no source is not a test.
        self::assertNotSame('', trim($case->source), sprintf('Case "%s" has no source.', $case->id));
    }

    public function testEveryShareSumsToUnityAcrossTheWholeSuite(): void
    {
        $calculator = new Calculator();

        foreach (CaseRegistry::all() as $case) {
            if ($case->expectError !== null) {
                continue;
            }
            $result = $calculator->calculate($case->input);
            self::assertTrue(
                $result->distributed->add($result->undistributed)->equals(Fraction::one()),
                sprintf('Case "%s" does not account for the whole estate.', $case->id)
            );
        }
    }

    public function testTheSuiteCoversEveryRequiredDoctrine(): void
    {
        $groups = [];
        foreach (CaseRegistry::all() as $case) {
            $groups[$case->group] = true;
        }

        foreach (['single_heir', 'spouse', 'descendants', 'siblings', 'awl', 'radd', 'named_case', 'exclusion', 'madhhab', 'mflo_1961', 'estate', 'validation'] as $required) {
            self::assertArrayHasKey($required, $groups, sprintf('No cases cover "%s".', $required));
        }
    }

    /** @return iterable<string,array{TestCaseDefinition}> */
    public static function cases(): iterable
    {
        foreach (CaseRegistry::all() as $case) {
            yield $case->id => [$case];
        }
    }
}
