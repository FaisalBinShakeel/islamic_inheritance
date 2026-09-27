<?php

declare(strict_types=1);

namespace Faraid;

use Faraid\Exception\CalculationError;

/** The engine's output. Immutable, and serialisable straight to JSON. */
final class Result
{
    /**
     * @param list<Share>     $shares
     * @param list<Exclusion> $excluded
     * @param list<string>    $warnings
     */
    public function __construct(
        public readonly array $shares,
        public readonly array $excluded,
        public readonly array $warnings,
        public readonly string $madhhab,
        public readonly bool $awlApplied,
        public readonly bool $raddApplied,
        public readonly bool $mfloApplied,
        public readonly ?float $estateValue,
        public readonly ?float $netEstate,
        public readonly float $funeral,
        public readonly float $debts,
        public readonly float $wasiyyah,
        public readonly Fraction $distributed,
        public readonly Fraction $undistributed,
        /** The case name when a named doctrine decided the outcome. */
        public readonly ?string $specialCase = null,
    ) {
    }

    /**
     * Lowest common denominator that expresses every share exactly — the
     * "denominator" a Faraid table is normally presented over (6, 12, 24, or
     * an awl figure such as 27).
     */
    public function denominator(): int
    {
        $denominator = 1;
        foreach ($this->shares as $share) {
            $denominator = Fraction::lcm($denominator, $share->share->denominator);
        }

        return max($denominator, 1);
    }

    public function share(HeirType $heir): ?Share
    {
        foreach ($this->shares as $share) {
            if ($share->heir === $heir) {
                return $share;
            }
        }

        return null;
    }

    public function isExcluded(HeirType $heir): bool
    {
        foreach ($this->excluded as $exclusion) {
            if ($exclusion->heir === $heir) {
                return true;
            }
        }

        return false;
    }

    /**
     * The shares must account for the whole estate, or for the whole estate
     * minus an explicitly reported remainder. Anything else is a bug.
     */
    public function assertBalanced(): void
    {
        $total = $this->distributed->add($this->undistributed);
        if (!$total->equals(Fraction::one())) {
            throw new CalculationError(sprintf(
                'Shares do not sum to unity: distributed %s + undistributed %s = %s.',
                (string) $this->distributed,
                (string) $this->undistributed,
                (string) $total
            ));
        }
    }

    public function toArray(): array
    {
        $denominator = $this->denominator();

        $shares = [];
        foreach ($this->shares as $share) {
            $row = [
                'heir' => $share->heir->value,
                'count' => $share->count,
                'numerator' => $share->share->numeratorOver($denominator),
                'denominator' => $denominator,
                'fraction' => (string) $share->share,
                'per_head_fraction' => (string) $share->perHead(),
                'percent' => $share->share->percent(4),
                'basis' => $share->basis,
                'reason_key' => $share->reasonKey,
            ];
            if ($share->via !== null) {
                $row['via'] = $share->via;
            }
            if ($this->netEstate !== null) {
                $row['amount'] = $share->share->applyTo($this->netEstate);
                $row['per_head_amount'] = $share->perHead()->applyTo($this->netEstate);
            }
            $shares[] = $row;
        }

        $excluded = [];
        foreach ($this->excluded as $exclusion) {
            $excluded[] = array_filter([
                'heir' => $exclusion->heir->value,
                'count' => $exclusion->count,
                'reason_key' => $exclusion->reasonKey,
                'kind' => $exclusion->kind,
                'excluded_by' => $exclusion->excludedBy?->value,
            ], static fn ($value) => $value !== null);
        }

        return [
            'madhhab' => $this->madhhab,
            'estate_value' => $this->estateValue,
            'net_estate' => $this->netEstate,
            'deductions' => [
                'funeral' => $this->funeral,
                'debts' => $this->debts,
                'wasiyyah' => $this->wasiyyah,
            ],
            'denominator' => $denominator,
            'awl_applied' => $this->awlApplied,
            'radd_applied' => $this->raddApplied,
            'mflo_1961_applied' => $this->mfloApplied,
            'special_case' => $this->specialCase,
            'distributed' => (string) $this->distributed,
            'undistributed' => (string) $this->undistributed,
            'shares' => $shares,
            'excluded' => $excluded,
            'warnings' => $this->warnings,
        ];
    }
}
