<?php

declare(strict_types=1);

namespace Faraid;

use Faraid\Exception\CalculationError;

/**
 * Mutable scratch space for a single calculation.
 *
 * The Calculator itself stays stateless — one of these is created per run and
 * thrown away — so two calculations can never contaminate each other.
 *
 * @internal
 */
final class CalculationState
{
    /** @var array<string,int> */
    public array $counts;

    /** @var array<string,int> heir counts before any hajb was applied */
    public readonly array $originalCounts;

    /** @var array<string,Share> keyed by heir value, in insertion order */
    private array $shares = [];

    /** @var list<Exclusion> */
    public array $excluded = [];

    /** @var list<string> */
    private array $warnings = [];

    public bool $hasDescendant = false;
    public bool $hasMaleDescendant = false;
    public bool $hasFemaleDescendantInheriting = false;
    public bool $fullSistersAreResiduaryWithDaughters = false;
    public bool $consanguineSistersAreResiduaryWithDaughters = false;
    /** Zayd's doctrine: outside the Hanafi school the sisters join the grandfather as residuaries. */
    public bool $siblingsCompeteWithGrandfather = false;

    public bool $awlApplied = false;
    public bool $raddApplied = false;
    public bool $mfloApplied = false;
    public ?string $specialCase = null;
    public Fraction $undistributed;

    /** @var list<array{gender:string,sons:int,daughters:int,label:string}> */
    public array $representation = [];
    public int $virtualSons = 0;
    public int $virtualDaughters = 0;

    public function __construct(public readonly Input $input)
    {
        $this->counts = $input->counts;
        $this->originalCounts = $input->counts;
        $this->undistributed = Fraction::zero();

        foreach ($input->warnings as $warning) {
            $this->addWarning($warning);
        }
        foreach ($input->madhhab->standingNotes() as $note) {
            $this->addWarning($note);
        }

        foreach ($input->disqualified as $entry) {
            $type = HeirType::tryFrom(self::singularFor($entry['heir']));
            if ($type === null) {
                continue;
            }
            $this->excluded[] = new Exclusion(
                $type,
                $entry['count'],
                'disqualified_' . $entry['reason'],
                'disqualification',
            );
        }
    }

    public function live(HeirType $type): int
    {
        return $this->counts[$type->inputKey()] ?? 0;
    }

    public function exclude(HeirType $type, string $reasonKey, ?HeirType $by = null): void
    {
        $count = $this->live($type);
        if ($count === 0) {
            return;
        }

        $this->counts[$type->inputKey()] = 0;
        $this->excluded[] = new Exclusion($type, $count, $reasonKey, 'hajb_hirman', $by);
    }

    public function addShare(Share $share): void
    {
        if (isset($this->shares[$share->heir->value]) && $share->via === null) {
            throw new CalculationError(sprintf('Share for %s assigned twice.', $share->heir->value));
        }

        $key = $share->via === null
            ? $share->heir->value
            : $share->heir->value . '#' . $share->via;

        $this->shares[$key] = $share;
    }

    public function setShare(Share $share): void
    {
        $key = $share->via === null
            ? $share->heir->value
            : $share->heir->value . '#' . $share->via;
        $this->shares[$key] = $share;
    }

    public function shareFor(HeirType $type): ?Share
    {
        return $this->shares[$type->value] ?? null;
    }

    public function removeShare(HeirType $type): void
    {
        unset($this->shares[$type->value]);
    }

    public function replaceCount(HeirType $type, int $count): void
    {
        $share = $this->shareFor($type);
        if ($share === null) {
            return;
        }
        $this->shares[$type->value] = new Share(
            $share->heir,
            $count,
            $share->share,
            $share->basis,
            $share->reasonKey,
            $share->via,
        );
    }

    /** @return list<Share> */
    public function shares(): array
    {
        return array_values($this->shares);
    }

    public function totalAssigned(): Fraction
    {
        return Fraction::sum(array_map(static fn (Share $s) => $s->share, $this->shares));
    }

    public function addWarning(string $key): void
    {
        if (!in_array($key, $this->warnings, true)) {
            $this->warnings[] = $key;
        }
    }

    /**
     * Siblings of every kind, counted before hajb: they reduce the mother to a
     * sixth even when the father has excluded them from inheriting.
     */
    public function siblingCountForMothersShare(): int
    {
        return $this->originalCounts['full_brothers']
            + $this->originalCounts['full_sisters']
            + $this->originalCounts['consanguine_brothers']
            + $this->originalCounts['consanguine_sisters']
            + $this->originalCounts['uterine_siblings'];
    }

    /**
     * True when the surviving heirs are exactly the given types (a spouse
     * optionally allowed alongside) and nobody else.
     *
     * @param list<HeirType> $types
     */
    public function survivorsAreExactly(array $types, bool $allowSpouse = false): bool
    {
        $allowed = array_map(static fn (HeirType $t) => $t->inputKey(), $types);
        if ($allowSpouse) {
            $allowed[] = 'husband';
            $allowed[] = 'wives';
        }

        foreach ($this->counts as $key => $count) {
            if ($count > 0 && !in_array($key, $allowed, true)) {
                return false;
            }
        }

        return $this->representation === [];
    }

    public function toResult(): Result
    {
        $distributed = $this->totalAssigned();

        $netEstate = null;
        if ($this->input->estateValue !== null) {
            $netEstate = round(
                $this->input->estateValue - $this->input->funeral - $this->input->debts - $this->input->wasiyyah,
                2
            );
        }

        if ($this->undistributed->isPositive()) {
            $this->addWarning('estate_not_fully_distributed');
        }

        return new Result(
            $this->shares(),
            $this->excluded,
            $this->warnings,
            $this->input->madhhab->key(),
            $this->awlApplied,
            $this->raddApplied,
            $this->mfloApplied,
            $this->input->estateValue,
            $netEstate,
            $this->input->funeral,
            $this->input->debts,
            $this->input->wasiyyah,
            $distributed,
            $this->undistributed,
            $this->specialCase,
        );
    }

    private static function singularFor(string $inputKey): string
    {
        foreach (HeirType::cases() as $type) {
            if ($type->inputKey() === $inputKey) {
                return $type->value;
            }
        }

        return $inputKey;
    }
}
