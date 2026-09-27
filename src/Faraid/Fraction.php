<?php

declare(strict_types=1);

namespace Faraid;

use Faraid\Exception\CalculationError;

/**
 * Exact rational number built on integers only.
 *
 * Faraid works in sixths, eighths and thirds, and awl produces denominators
 * such as 27. Floating point cannot represent those without drift, and drift
 * in this domain means a wrong inheritance share, so every number inside the
 * engine is a Fraction.
 */
final class Fraction implements \Stringable
{
    public readonly int $numerator;
    public readonly int $denominator;

    public function __construct(int $numerator, int $denominator = 1)
    {
        if ($denominator === 0) {
            throw new CalculationError('Fraction denominator cannot be zero.');
        }

        if ($denominator < 0) {
            $numerator = -$numerator;
            $denominator = -$denominator;
        }

        $divisor = self::gcd(abs($numerator), $denominator);
        if ($divisor > 1) {
            $numerator = intdiv($numerator, $divisor);
            $denominator = intdiv($denominator, $divisor);
        }

        $this->numerator = $numerator;
        $this->denominator = $denominator;
    }

    public static function of(int $numerator, int $denominator = 1): self
    {
        return new self($numerator, $denominator);
    }

    public static function zero(): self
    {
        return new self(0, 1);
    }

    public static function one(): self
    {
        return new self(1, 1);
    }

    public function add(self $other): self
    {
        return new self(
            $this->numerator * $other->denominator + $other->numerator * $this->denominator,
            $this->denominator * $other->denominator
        );
    }

    public function subtract(self $other): self
    {
        return $this->add($other->negated());
    }

    public function multiply(self $other): self
    {
        return new self(
            $this->numerator * $other->numerator,
            $this->denominator * $other->denominator
        );
    }

    public function divide(self $other): self
    {
        if ($other->isZero()) {
            throw new CalculationError('Division by a zero fraction.');
        }

        return new self(
            $this->numerator * $other->denominator,
            $this->denominator * $other->numerator
        );
    }

    public function negated(): self
    {
        return new self(-$this->numerator, $this->denominator);
    }

    public function isZero(): bool
    {
        return $this->numerator === 0;
    }

    public function isPositive(): bool
    {
        return $this->numerator > 0;
    }

    public function isNegative(): bool
    {
        return $this->numerator < 0;
    }

    /** @return int -1, 0 or 1 */
    public function compare(self $other): int
    {
        return $this->numerator * $other->denominator <=> $other->numerator * $this->denominator;
    }

    public function equals(self $other): bool
    {
        return $this->compare($other) === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return $this->compare($other) < 0;
    }

    public static function max(self ...$fractions): self
    {
        $best = array_shift($fractions);
        if ($best === null) {
            throw new CalculationError('max() needs at least one fraction.');
        }
        foreach ($fractions as $candidate) {
            if ($candidate->isGreaterThan($best)) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /** @param iterable<self> $fractions */
    public static function sum(iterable $fractions): self
    {
        $total = self::zero();
        foreach ($fractions as $fraction) {
            $total = $total->add($fraction);
        }

        return $total;
    }

    /**
     * Numerator of this fraction expressed over the given denominator.
     * Throws rather than rounding, because a non-integer result here would
     * mean the chosen common denominator was wrong.
     */
    public function numeratorOver(int $denominator): int
    {
        $scaled = $this->numerator * $denominator;
        if ($scaled % $this->denominator !== 0) {
            throw new CalculationError(sprintf(
                '%s cannot be expressed exactly over denominator %d.',
                (string) $this,
                $denominator
            ));
        }

        return intdiv($scaled, $this->denominator);
    }

    public function toFloat(): float
    {
        return $this->numerator / $this->denominator;
    }

    public function percent(int $precision = 4): float
    {
        return round($this->numerator * 100 / $this->denominator, $precision);
    }

    /** Amount of a money value this fraction represents, rounded to $precision decimals. */
    public function applyTo(float $amount, int $precision = 2): float
    {
        return round($amount * $this->numerator / $this->denominator, $precision);
    }

    public function __toString(): string
    {
        if ($this->denominator === 1) {
            return (string) $this->numerator;
        }

        return $this->numerator . '/' . $this->denominator;
    }

    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a === 0 ? 1 : $a;
    }

    public static function lcm(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return intdiv(abs($a * $b), self::gcd($a, $b));
    }
}
