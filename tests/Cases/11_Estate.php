<?php

declare(strict_types=1);

/**
 * Estate deductions, money amounts, and the input validation that stops
 * nonsense reaching the rule engine.
 *
 * Shares are calculated on the net estate: funeral and burial expenses, then
 * debts, then bequests capped at one third of what remains.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'estate_deductions_worked_example',
        'title' => 'Five million, less funeral and debts, between a wife, mother, two sons and three daughters',
        'group' => 'estate',
        'source' => "Order of deductions from Qur'an 4:11-12 (min ba'di wasiyyatin yusi biha aw dayn) with funeral expenses taken first by consensus; worked example from the product specification.",
        'input' => $male + [
            'estate_value' => 5000000,
            'deductions' => ['funeral' => 50000, 'debts' => 200000, 'wasiyyah' => 0],
            'heirs' => ['wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3],
        ],
        'expect' => [
            'net_estate' => 4750000.0,
            'shares' => ['wife' => '1/8', 'mother' => '1/6', 'son' => '17/42', 'daughter' => '17/56'],
            'amounts' => ['wife' => 593750, 'mother' => 791666.67],
        ],
    ],
    [
        'id' => 'wasiyyah_capped_at_one_third',
        'title' => 'A bequest over one third is capped, and the cap is reported',
        'group' => 'estate',
        'source' => "Hadith of Sa'd b. Abi Waqqas: 'one third, and one third is a great deal' (al-Bukhari, Muslim).",
        'input' => $male + [
            'estate_value' => 300000,
            'deductions' => ['funeral' => 0, 'debts' => 0, 'wasiyyah' => 200000],
            'heirs' => ['sons' => 1],
        ],
        'expect' => [
            'net_estate' => 200000.0,
            'shares' => ['son' => '1'],
            'amounts' => ['son' => 200000],
            'warnings' => ['wasiyyah_capped_at_one_third'],
        ],
    ],
    [
        'id' => 'wasiyyah_within_the_cap_is_untouched',
        'title' => 'A bequest within the cap passes through unchanged',
        'group' => 'estate',
        'source' => 'The cap bites only above one third of the estate remaining after funeral expenses and debts.',
        'input' => $male + [
            'estate_value' => 300000,
            'deductions' => ['funeral' => 0, 'debts' => 0, 'wasiyyah' => 90000],
            'heirs' => ['sons' => 1],
        ],
        'expect' => [
            'net_estate' => 210000.0,
            'shares' => ['son' => '1'],
            'not_warnings' => ['wasiyyah_capped_at_one_third'],
        ],
    ],
    [
        'id' => 'no_estate_value_still_calculates',
        'title' => 'Shares are produced without an estate value',
        'group' => 'estate',
        'source' => 'Product requirement: entering nothing calculates on one hundred per cent.',
        'input' => $male + ['heirs' => ['wives' => 1, 'sons' => 1]],
        'expect' => ['shares' => ['wife' => '1/8', 'son' => '7/8'], 'net_estate' => null],
    ],
    [
        'id' => 'deductions_exceeding_estate_blocked',
        'title' => 'Funeral expenses and debts above the estate are refused',
        'group' => 'validation',
        'source' => 'Product requirement: block with a clear message rather than producing negative shares.',
        'input' => $male + [
            'estate_value' => 100000,
            'deductions' => ['funeral' => 50000, 'debts' => 80000, 'wasiyyah' => 0],
            'heirs' => ['sons' => 1],
        ],
        'expect_error' => 'exceed the estate value',
    ],
    [
        'id' => 'husband_and_wives_together_blocked',
        'title' => 'A husband and wives together are refused',
        'group' => 'validation',
        'source' => 'Product requirement: block, because no estate can have both.',
        'input' => ['madhhab' => 'hanafi', 'deceased_gender' => 'male', 'heirs' => ['husband' => 1, 'wives' => 1]],
        'expect_error' => 'both a husband and wives',
    ],
    [
        'id' => 'wives_for_a_female_deceased_blocked',
        'title' => 'A female deceased cannot leave wives',
        'group' => 'validation',
        'source' => 'Internal consistency between deceased_gender and the spouse entered.',
        'input' => $female + ['heirs' => ['wives' => 1]],
        'expect_error' => 'cannot leave wives',
    ],
    [
        'id' => 'no_heirs_blocked',
        'title' => 'An empty heir set is refused',
        'group' => 'validation',
        'source' => 'Product requirement: the interface shows a prompt; the engine refuses rather than returning an empty distribution.',
        'input' => $male + ['heirs' => []],
        'expect_error' => 'No heirs were entered',
    ],
    [
        'id' => 'unknown_heir_key_blocked',
        'title' => 'An unrecognised heir key is refused rather than ignored',
        'group' => 'validation',
        'source' => 'A silently dropped heir is worse than an error; distant kindred are out of scope for v1.',
        'input' => $male + ['heirs' => ['cousins_wife' => 1]],
        'expect_error' => 'Unknown heir key',
    ],
    [
        'id' => 'negative_heir_count_blocked',
        'title' => 'A negative heir count is refused',
        'group' => 'validation',
        'source' => 'Basic input sanity.',
        'input' => $male + ['heirs' => ['sons' => -1]],
        'expect_error' => 'cannot be negative',
    ],
    [
        'id' => 'deductions_without_estate_value_blocked',
        'title' => 'Deductions without an estate value are refused',
        'group' => 'validation',
        'source' => 'A deduction has no meaning without a total to deduct it from.',
        'input' => $male + [
            'deductions' => ['funeral' => 50000, 'debts' => 0, 'wasiyyah' => 0],
            'heirs' => ['sons' => 1],
        ],
        'expect_error' => 'Deductions were supplied without an estate_value',
    ],
];
