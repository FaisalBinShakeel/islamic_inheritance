<?php

declare(strict_types=1);

namespace Faraid;

use Faraid\Exception\InvalidInput;
use Faraid\Madhhab\MadhhabRules;

/**
 * Normalises and validates the raw input array into something the engine can
 * trust. Nothing downstream re-checks these invariants, so everything that
 * could be nonsense is caught here.
 */
final class Input
{
    /** @var array<string,int> heir input key => count */
    public readonly array $counts;

    /** @var list<array{gender:string,sons:int,daughters:int,own_heirs:array<string,int>}> */
    public readonly array $predeceasedChildren;

    /** @var list<array{heir:string,reason:string,count:int}> */
    public readonly array $disqualified;

    /** @var list<string> reason keys */
    public readonly array $warnings;

    public readonly MadhhabRules $madhhab;
    public readonly string $deceasedGender;
    public readonly bool $applyMflo1961;
    /** settled (Kamal Khan / Zainab) | textual */
    public readonly string $mfloConstruction;
    public readonly ?float $estateValue;
    public readonly float $funeral;
    public readonly float $debts;
    public readonly float $wasiyyah;
    public readonly float $wasiyyahRequested;

    private function __construct(
        array $counts,
        array $predeceasedChildren,
        array $disqualified,
        array $warnings,
        MadhhabRules $madhhab,
        string $deceasedGender,
        bool $applyMflo1961,
        string $mfloConstruction,
        ?float $estateValue,
        float $funeral,
        float $debts,
        float $wasiyyah,
        float $wasiyyahRequested,
    ) {
        $this->counts = $counts;
        $this->predeceasedChildren = $predeceasedChildren;
        $this->disqualified = $disqualified;
        $this->warnings = $warnings;
        $this->madhhab = $madhhab;
        $this->deceasedGender = $deceasedGender;
        $this->applyMflo1961 = $applyMflo1961;
        $this->mfloConstruction = $mfloConstruction;
        $this->estateValue = $estateValue;
        $this->funeral = $funeral;
        $this->debts = $debts;
        $this->wasiyyah = $wasiyyah;
        $this->wasiyyahRequested = $wasiyyahRequested;
    }

    /** All heir input keys the engine accepts, each defaulting to zero. */
    public static function heirKeys(): array
    {
        $keys = [];
        foreach (HeirType::cases() as $type) {
            if ($type === HeirType::PredeceasedChildsSon || $type === HeirType::PredeceasedChildsDaughter) {
                continue;
            }
            $keys[] = $type->inputKey();
        }

        return $keys;
    }

    public static function fromArray(array $raw): self
    {
        $warnings = [];

        $madhhabKey = $raw['madhhab'] ?? null;
        if (!is_string($madhhabKey) || trim($madhhabKey) === '') {
            // The engine never guesses a school. The UI may suggest one from
            // the locale, but it must send an explicit choice.
            throw new InvalidInput('A madhhab must be supplied explicitly; the engine does not assume one.');
        }
        $madhhab = MadhhabRules::fromKey($madhhabKey);

        $gender = strtolower((string) ($raw['deceased_gender'] ?? ''));
        if (!in_array($gender, ['male', 'female'], true)) {
            throw new InvalidInput('deceased_gender must be "male" or "female".');
        }

        $heirs = $raw['heirs'] ?? [];
        if (!is_array($heirs)) {
            throw new InvalidInput('heirs must be an array.');
        }

        $counts = [];
        foreach (self::heirKeys() as $key) {
            $counts[$key] = self::readCount($heirs, $key);
        }

        // Legacy/convenience key from the product specification: a single
        // "grandmothers" count with no side stated. Treated as maternal, which
        // is the weaker exclusion (mother only), and flagged, because a
        // paternal grandmother is additionally excluded by the father.
        $unsidedGrandmothers = self::readCount($heirs, 'grandmothers');
        if ($unsidedGrandmothers > 0) {
            $counts['maternal_grandmother'] += $unsidedGrandmothers;
            $warnings[] = 'grandmother_side_unspecified';
        }

        foreach (array_keys($heirs) as $suppliedKey) {
            if (!is_string($suppliedKey)) {
                continue;
            }
            if ($suppliedKey !== 'grandmothers' && !in_array($suppliedKey, self::heirKeys(), true)) {
                throw new InvalidInput(sprintf('Unknown heir key "%s".', $suppliedKey));
            }
        }

        $counts['husband'] = min($counts['husband'], 1);
        foreach (['father', 'mother', 'paternal_grandfather'] as $singular) {
            $counts[$singular] = min($counts[$singular], 1);
        }

        if ($counts['husband'] > 0 && $counts['wives'] > 0) {
            throw new InvalidInput('A deceased person cannot leave both a husband and wives.');
        }
        if ($gender === 'male' && $counts['husband'] > 0) {
            throw new InvalidInput('A male deceased cannot leave a husband; set deceased_gender to "female".');
        }
        if ($gender === 'female' && $counts['wives'] > 0) {
            throw new InvalidInput('A female deceased cannot leave wives; set deceased_gender to "male".');
        }
        if ($counts['wives'] > 4) {
            $warnings[] = 'more_than_four_wives';
        }

        $predeceased = self::readPredeceasedChildren($raw['predeceased_children'] ?? []);
        $disqualified = self::readDisqualifications($raw['disqualified'] ?? [], $counts);

        if (array_sum($counts) === 0 && $predeceased === []) {
            throw new InvalidInput('No heirs were entered.');
        }

        if ($counts['father'] > 0 && $counts['paternal_grandfather'] > 0) {
            // Allowed, but the grandfather will be excluded. Said out loud
            // rather than silently dropped.
            $warnings[] = 'grandfather_present_but_excluded_by_father';
        }

        if ($predeceased !== [] && !($raw['apply_mflo_1961'] ?? false)) {
            $warnings[] = 'predeceased_children_ignored_without_mflo';
        }

        $estateValue = isset($raw['estate_value']) ? (float) $raw['estate_value'] : null;
        if ($estateValue !== null && $estateValue < 0) {
            throw new InvalidInput('estate_value cannot be negative.');
        }

        $deductions = $raw['deductions'] ?? [];
        if (!is_array($deductions)) {
            throw new InvalidInput('deductions must be an array.');
        }
        $funeral = self::readAmount($deductions, 'funeral');
        $debts = self::readAmount($deductions, 'debts');
        $wasiyyahRequested = self::readAmount($deductions, 'wasiyyah');
        $wasiyyah = $wasiyyahRequested;

        if ($estateValue === null && ($funeral > 0 || $debts > 0 || $wasiyyahRequested > 0)) {
            throw new InvalidInput('Deductions were supplied without an estate_value.');
        }

        if ($estateValue !== null) {
            if ($funeral + $debts > $estateValue) {
                throw new InvalidInput('Funeral expenses and debts exceed the estate value.');
            }

            // Wasiyyah is capped at one third of what remains after funeral
            // expenses and debts (Step 0 of the rule specification).
            $afterDebts = $estateValue - $funeral - $debts;
            $cap = $afterDebts / 3;
            if ($wasiyyahRequested > $cap + 1e-9) {
                $wasiyyah = round($cap, 2);
                $warnings[] = 'wasiyyah_capped_at_one_third';
            }
        }

        return new self(
            $counts,
            $predeceased,
            $disqualified,
            array_values(array_unique($warnings)),
            $madhhab,
            $gender,
            (bool) ($raw['apply_mflo_1961'] ?? false),
            ($raw['mflo_construction'] ?? 'settled') === 'textual' ? 'textual' : 'settled',
            $estateValue,
            $funeral,
            $debts,
            $wasiyyah,
            $wasiyyahRequested,
        );
    }

    public function count(HeirType $type): int
    {
        return $this->counts[$type->inputKey()] ?? 0;
    }

    private static function readCount(array $heirs, string $key): int
    {
        $value = $heirs[$key] ?? 0;
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value)) {
            $count = $value;
        } elseif (is_string($value) && ctype_digit($value)) {
            $count = (int) $value;
        } elseif (is_float($value) && floor($value) === $value) {
            $count = (int) $value;
        } else {
            throw new InvalidInput(sprintf('Heir count for "%s" must be a whole number or a boolean.', $key));
        }

        if ($count < 0) {
            throw new InvalidInput(sprintf('Heir count for "%s" cannot be negative.', $key));
        }

        return $count;
    }

    private static function readAmount(array $deductions, string $key): float
    {
        $value = $deductions[$key] ?? 0;
        if (!is_numeric($value)) {
            throw new InvalidInput(sprintf('Deduction "%s" must be numeric.', $key));
        }
        $amount = (float) $value;
        if ($amount < 0) {
            throw new InvalidInput(sprintf('Deduction "%s" cannot be negative.', $key));
        }

        return $amount;
    }

    /** @return list<array{gender:string,sons:int,daughters:int,own_heirs:array<string,int>}> */
    private static function readPredeceasedChildren(mixed $raw): array
    {
        if ($raw === [] || $raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            throw new InvalidInput('predeceased_children must be a list.');
        }

        $children = [];
        foreach ($raw as $child) {
            if (!is_array($child)) {
                throw new InvalidInput('Each predeceased child must be an array.');
            }
            $gender = strtolower((string) ($child['gender'] ?? ''));
            if (!in_array($gender, ['male', 'female'], true)) {
                throw new InvalidInput('A predeceased child needs a gender of "male" or "female".');
            }
            $sons = self::readCount($child, 'sons');
            $daughters = self::readCount($child, 'daughters');

            // Under the construction Pakistani courts actually apply, the
            // notional share is distributed among ALL of the predeceased
            // child's own heirs, not only their children — so the caller may
            // give that child's full heir set. Their own sons and daughters
            // remain the shorthand, because that is the common case.
            $ownHeirs = [];
            foreach (self::heirKeys() as $key) {
                $count = self::readCount(is_array($child['heirs'] ?? null) ? $child['heirs'] : [], $key);
                if ($count > 0) {
                    $ownHeirs[$key] = $count;
                }
            }
            if ($sons > 0) {
                $ownHeirs['sons'] = ($ownHeirs['sons'] ?? 0) + $sons;
            }
            if ($daughters > 0) {
                $ownHeirs['daughters'] = ($ownHeirs['daughters'] ?? 0) + $daughters;
            }

            if ($ownHeirs === []) {
                // A predeceased child with no surviving heirs of their own
                // represents nobody, so they cannot take a share.
                throw new InvalidInput('A predeceased child with no surviving heirs cannot inherit by representation.');
            }

            $children[] = [
                'gender' => $gender,
                'sons' => $sons,
                'daughters' => $daughters,
                'own_heirs' => $ownHeirs,
            ];
        }

        return $children;
    }

    /** @return list<array{heir:string,reason:string,count:int}> */
    private static function readDisqualifications(mixed $raw, array &$counts): array
    {
        if ($raw === [] || $raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            throw new InvalidInput('disqualified must be a list.');
        }

        $applied = [];
        foreach ($raw as $entry) {
            if (is_string($entry)) {
                $entry = ['heir' => $entry];
            }
            if (!is_array($entry)) {
                throw new InvalidInput('Each disqualification must be a string or an array.');
            }

            $key = (string) ($entry['heir'] ?? '');
            if (!array_key_exists($key, $counts)) {
                throw new InvalidInput(sprintf('Unknown heir key "%s" in disqualified.', $key));
            }

            $reason = strtolower((string) ($entry['reason'] ?? 'unspecified'));
            if (!in_array($reason, ['homicide', 'different_religion', 'unspecified'], true)) {
                throw new InvalidInput(sprintf('Unknown disqualification reason "%s".', $reason));
            }

            $count = array_key_exists('count', $entry) ? self::readCount($entry, 'count') : $counts[$key];
            $count = min($count, $counts[$key]);
            if ($count === 0) {
                continue;
            }

            $counts[$key] -= $count;
            $applied[] = ['heir' => $key, 'reason' => $reason, 'count' => $count];
        }

        return $applied;
    }
}
