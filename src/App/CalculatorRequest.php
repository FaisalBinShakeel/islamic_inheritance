<?php

declare(strict_types=1);

namespace App;

/**
 * Maps the calculator form onto the engine's input contract.
 *
 * All of the translation from "what the form called it" to "what the engine
 * calls it" happens here, so the engine's contract stays clean and the form
 * can be rearranged without touching a rule.
 */
final class CalculatorRequest
{
    private const HEIR_FIELDS = [
        'husband', 'wives', 'father', 'mother',
        'paternal_grandfather', 'maternal_grandmother', 'paternal_grandmother',
        'sons', 'daughters', 'sons_sons', 'sons_daughters',
        'full_brothers', 'full_sisters',
        'consanguine_brothers', 'consanguine_sisters', 'uterine_siblings',
        'full_brother_sons', 'consanguine_brother_sons',
        'paternal_uncles', 'consanguine_paternal_uncles',
        'paternal_uncle_sons', 'consanguine_paternal_uncle_sons',
    ];

    /** @return array{engine:array,form:array,hasHeirs:bool} */
    public static function fromForm(array $form): array
    {
        $heirs = [];
        $kept = [];
        foreach (self::HEIR_FIELDS as $field) {
            $count = self::count($form[$field] ?? 0);
            $kept[$field] = $count;
            if ($count > 0) {
                $heirs[$field] = $count;
            }
        }

        $gender = ($form['deceased_gender'] ?? 'male') === 'female' ? 'female' : 'male';

        // Without JavaScript the form carries the field for the other sex too.
        // Clearing the empty one is tidying up; clearing a field someone
        // actually filled in would be hiding their mistake, so when both are
        // present both are passed on and the engine refuses the combination.
        $bothSpousesEntered = ($heirs['husband'] ?? 0) > 0 && ($heirs['wives'] ?? 0) > 0;
        if (!$bothSpousesEntered) {
            if ($gender === 'male') {
                unset($heirs['husband']);
                $kept['husband'] = 0;
            } else {
                unset($heirs['wives']);
                $kept['wives'] = 0;
            }
        }

        $jurisdiction = ($form['jurisdiction'] ?? 'classical') === 'mflo' ? 'mflo' : 'classical';

        $engine = [
            'madhhab' => trim((string) ($form['madhhab'] ?? '')),
            'deceased_gender' => $gender,
            'apply_mflo_1961' => $jurisdiction === 'mflo',
            'heirs' => $heirs,
        ];

        $predeceased = self::predeceased($form);
        if ($predeceased !== []) {
            $engine['predeceased_children'] = $predeceased;
        }

        $estate = self::amount($form['estate_value'] ?? null);
        if ($estate !== null) {
            $engine['estate_value'] = $estate;
            $deductions = [
                'funeral' => self::amount($form['funeral'] ?? null) ?? 0.0,
                'debts' => self::amount($form['debts'] ?? null) ?? 0.0,
                'wasiyyah' => self::amount($form['wasiyyah'] ?? null) ?? 0.0,
            ];
            if (array_sum($deductions) > 0) {
                $engine['deductions'] = $deductions;
            }
        }

        $form2 = $kept + [
            'madhhab' => $engine['madhhab'],
            'deceased_gender' => $gender,
            'jurisdiction' => $jurisdiction,
            'estate_value' => $form['estate_value'] ?? '',
            'funeral' => $form['funeral'] ?? '',
            'debts' => $form['debts'] ?? '',
            'wasiyyah' => $form['wasiyyah'] ?? '',
        ];

        return [
            'engine' => $engine,
            'form' => $form2,
            'hasHeirs' => $heirs !== [] || $predeceased !== [],
        ];
    }

    /** A stable signature of the heir combination, for the analytics table. */
    public static function signature(array $engineInput): string
    {
        $heirs = $engineInput['heirs'] ?? [];
        ksort($heirs);
        $parts = [];
        foreach ($heirs as $heir => $count) {
            $parts[] = $heir . ':' . $count;
        }

        return mb_substr(implode(',', $parts), 0, 255);
    }

    /** @return list<array{gender:string,sons:int,daughters:int}> */
    private static function predeceased(array $form): array
    {
        $genders = (array) ($form['predeceased_gender'] ?? []);
        $sons = (array) ($form['predeceased_sons'] ?? []);
        $daughters = (array) ($form['predeceased_daughters'] ?? []);

        $children = [];
        foreach ($genders as $index => $gender) {
            $childSons = self::count($sons[$index] ?? 0);
            $childDaughters = self::count($daughters[$index] ?? 0);
            if ($childSons + $childDaughters === 0) {
                // A predeceased child with nobody to represent is dropped
                // quietly here rather than raised as an error while the user
                // is still filling the row in.
                continue;
            }
            $children[] = [
                'gender' => $gender === 'female' ? 'female' : 'male',
                'sons' => $childSons,
                'daughters' => $childDaughters,
            ];
        }

        return $children;
    }

    private static function count(mixed $value): int
    {
        if (is_array($value)) {
            return 0;
        }
        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return 0;
        }

        return max(0, min(50, (int) $value));
    }

    private static function amount(mixed $value): ?float
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return max(0.0, (float) $value);
    }
}
