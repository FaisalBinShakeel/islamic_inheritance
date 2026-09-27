<?php

declare(strict_types=1);

namespace Faraid\Tests;

use Faraid\Exception\CalculationError;
use Faraid\Fraction;
use PHPUnit\Framework\TestCase;

/**
 * Faraid works in thirds, sixths and eighths. Floating point cannot hold those
 * exactly, and a rounding drift here is a wrong inheritance share, so these
 * tests exist to keep the engine's arithmetic honest.
 */
final class FractionTest extends TestCase
{
    public function testThirdsAddBackToOneExactly(): void
    {
        $third = Fraction::of(1, 3);
        $total = $third->add($third)->add($third);

        self::assertTrue($total->equals(Fraction::one()));
        self::assertSame('1', (string) $total);
    }

    public function testSixthsAndEighthsDoNotDrift(): void
    {
        // The shares of al-Minbariyya before awl: 1/8 + 2/3 + 1/6 + 1/6.
        $total = Fraction::sum([
            Fraction::of(1, 8),
            Fraction::of(2, 3),
            Fraction::of(1, 6),
            Fraction::of(1, 6),
        ]);

        self::assertSame(27, $total->numeratorOver(24));
        self::assertSame('9/8', (string) $total);
        self::assertTrue($total->isGreaterThan(Fraction::one()));
    }

    public function testFractionsNormaliseOnConstruction(): void
    {
        self::assertSame('1/2', (string) Fraction::of(12, 24));
        self::assertSame('-1/2', (string) Fraction::of(1, -2));
        self::assertSame('2', (string) Fraction::of(4, 2));
    }

    public function testDivisionByZeroIsLoud(): void
    {
        $this->expectException(CalculationError::class);
        Fraction::one()->divide(Fraction::zero());
    }

    public function testZeroDenominatorIsLoud(): void
    {
        $this->expectException(CalculationError::class);
        new Fraction(1, 0);
    }

    public function testNumeratorOverRefusesAnInexactDenominator(): void
    {
        $this->expectException(CalculationError::class);
        Fraction::of(1, 7)->numeratorOver(24);
    }

    public function testNumeratorOverScalesExactly(): void
    {
        self::assertSame(3, Fraction::of(1, 8)->numeratorOver(24));
        self::assertSame(16, Fraction::of(2, 3)->numeratorOver(24));
    }

    public function testPercentAndAmount(): void
    {
        self::assertSame(12.5, Fraction::of(1, 8)->percent());
        self::assertSame(593750.0, Fraction::of(1, 8)->applyTo(4750000));
    }

    public function testMaxPicksTheLargest(): void
    {
        $best = Fraction::max(Fraction::of(1, 15), Fraction::of(1, 9), Fraction::of(1, 6));
        self::assertSame('1/6', (string) $best);
    }
}
