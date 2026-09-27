<?php

declare(strict_types=1);

/**
 * Spouse shares with and without a descendant — the hajb nuqsan that halves
 * the husband from a half to a quarter and the wife from a quarter to an
 * eighth. Also fixes the rule that all wives together take one portion.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'husband_with_son',
        'title' => 'Husband and one son',
        'group' => 'spouse',
        'source' => "Qur'an 4:12: the husband takes one quarter where a child survives.",
        'input' => $female + ['heirs' => ['husband' => 1, 'sons' => 1]],
        'expect' => ['shares' => ['husband' => '1/4', 'son' => '3/4'], 'denominator' => 4],
    ],
    [
        'id' => 'husband_with_daughter',
        'title' => 'Husband and one daughter',
        'group' => 'spouse',
        'source' => "Qur'an 4:11-12, then radd of the surplus to the daughter alone; the spouse is excluded from radd (Hanafi).",
        'input' => $female + ['heirs' => ['husband' => 1, 'daughters' => 1]],
        'expect' => ['shares' => ['husband' => '1/4', 'daughter' => '3/4'], 'radd' => true],
    ],
    [
        'id' => 'husband_with_full_brother',
        'title' => 'Husband and full brother, no descendant',
        'group' => 'spouse',
        'source' => "Qur'an 4:12; the brother takes the residue as asaba.",
        'input' => $female + ['heirs' => ['husband' => 1, 'full_brothers' => 1]],
        'expect' => ['shares' => ['husband' => '1/2', 'full_brother' => '1/2'], 'radd' => false],
    ],
    [
        'id' => 'wife_with_son',
        'title' => 'One wife and one son',
        'group' => 'spouse',
        'source' => "Qur'an 4:12: the wife takes one eighth where a child survives.",
        'input' => $male + ['heirs' => ['wives' => 1, 'sons' => 1]],
        'expect' => ['shares' => ['wife' => '1/8', 'son' => '7/8'], 'denominator' => 8],
    ],
    [
        'id' => 'wife_with_daughter',
        'title' => 'One wife and one daughter',
        'group' => 'spouse',
        'source' => "Qur'an 4:11-12, then radd to the daughter; the wife keeps her eighth and takes no part of the return.",
        'input' => $male + ['heirs' => ['wives' => 1, 'daughters' => 1]],
        'expect' => ['shares' => ['wife' => '1/8', 'daughter' => '7/8'], 'radd' => true],
    ],
    [
        'id' => 'four_wives_share_one_portion',
        'title' => 'Four wives and one son share a single eighth',
        'group' => 'spouse',
        'source' => "Qur'an 4:12: the wives' portion is one share between them all, however many they are.",
        'input' => $male + ['heirs' => ['wives' => 4, 'sons' => 1]],
        'expect' => [
            'shares' => ['wife' => '1/8', 'son' => '7/8'],
            'per_head' => ['wife' => '1/32'],
            'not_warnings' => ['more_than_four_wives'],
        ],
    ],
    [
        'id' => 'five_wives_warns',
        'title' => 'Five wives are calculated but flagged',
        'group' => 'spouse',
        'source' => 'Classical maximum of four wives at one time; the engine calculates but warns rather than refusing.',
        'input' => $male + ['heirs' => ['wives' => 5, 'sons' => 1]],
        'expect' => [
            'shares' => ['wife' => '1/8', 'son' => '7/8'],
            'warnings' => ['more_than_four_wives'],
        ],
    ],
    [
        'id' => 'two_wives_and_father',
        'title' => 'Two wives and the father',
        'group' => 'spouse',
        'source' => "Qur'an 4:12 with no descendant; the father takes the residue as asaba.",
        'input' => $male + ['heirs' => ['wives' => 2, 'father' => true]],
        'expect' => ['shares' => ['wife' => '1/4', 'father' => '3/4'], 'per_head' => ['wife' => '1/8']],
    ],
    [
        'id' => 'wife_with_sons_daughter_only',
        'title' => "Wife reduced to an eighth by a son's daughter",
        'group' => 'spouse',
        'source' => "The agnatic grandchild counts as a child for the spouse's hajb nuqsan. al-Sirajiyya, bab al-furud.",
        'input' => $male + ['heirs' => ['wives' => 1, 'sons_daughters' => 1]],
        'expect' => ['shares' => ['wife' => '1/8', 'sons_daughter' => '7/8'], 'radd' => true],
    ],
];
