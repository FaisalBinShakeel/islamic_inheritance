<?php

declare(strict_types=1);

/**
 * Awl: the fixed shares claim more than the estate, so the denominator is
 * raised to the sum of the numerators and every heir is reduced in the same
 * proportion. The three base denominators go up 6 -> 7, 8, 9, 10;
 * 12 -> 13, 15, 17; and 24 -> 27.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'awl_minbariyya_24_to_27',
        'title' => 'Al-Minbariyya: wife, two daughters, father and mother — 24 goes to 27',
        'group' => 'awl',
        'source' => "The case put to Ali b. Abi Talib on the pulpit of Kufa: 'her eighth has become a ninth'. Reported in the standard fara'id literature; al-Sirajiyya, bab al-awl.",
        'input' => $male + ['heirs' => ['wives' => 1, 'daughters' => 2, 'father' => true, 'mother' => true]],
        'expect' => [
            'shares' => ['wife' => '3/27', 'daughter' => '16/27', 'father' => '4/27', 'mother' => '4/27'],
            'denominator' => 27,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_6_to_7',
        'title' => 'Husband and two full sisters — 6 goes to 7',
        'group' => 'awl',
        'source' => "Qur'an 4:12 and 4:176; the first awl case decided in the caliphate of Umar. al-Sirajiyya, bab al-awl.",
        'input' => $female + ['heirs' => ['husband' => 1, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['husband' => '3/7', 'full_sister' => '4/7'],
            'denominator' => 7,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_6_to_8',
        'title' => 'Husband, one full sister and the mother — 6 goes to 8',
        'group' => 'awl',
        'source' => "A single sibling does not reduce the mother, so her third joins two halves. al-Sirajiyya, bab al-awl.",
        'input' => $female + ['heirs' => ['husband' => 1, 'full_sisters' => 1, 'mother' => true]],
        'expect' => [
            'shares' => ['husband' => '3/8', 'full_sister' => '3/8', 'mother' => '2/8'],
            'denominator' => 8,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_6_to_9',
        'title' => 'Husband, two uterine siblings and two full sisters — 6 goes to 9',
        'group' => 'awl',
        'source' => "Qur'an 4:12 and 4:176 taken together; a standard awl worked example. al-Sirajiyya, bab al-awl.",
        'input' => $female + ['heirs' => ['husband' => 1, 'uterine_siblings' => 2, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['husband' => '3/9', 'uterine_sibling' => '2/9', 'full_sister' => '4/9'],
            'denominator' => 9,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_6_to_10',
        'title' => 'Husband, mother, two uterine siblings and two full sisters — 6 goes to 10',
        'group' => 'awl',
        'source' => 'The largest awl on a base of six. al-Sirajiyya, bab al-awl.',
        'input' => $female + ['heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['husband' => '3/10', 'mother' => '1/10', 'uterine_sibling' => '2/10', 'full_sister' => '4/10'],
            'denominator' => 10,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_12_to_13',
        'title' => 'Wife, two full sisters and the mother — 12 goes to 13',
        'group' => 'awl',
        'source' => 'Standard awl on a base of twelve. al-Sirajiyya, bab al-awl.',
        'input' => $male + ['heirs' => ['wives' => 1, 'full_sisters' => 2, 'mother' => true]],
        'expect' => [
            'shares' => ['wife' => '3/13', 'full_sister' => '8/13', 'mother' => '2/13'],
            'denominator' => 13,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_12_to_15',
        'title' => 'Husband, two daughters, father and mother — 12 goes to 15',
        'group' => 'awl',
        'source' => 'Both parents take a sixth beside the daughters, and the husband a quarter. al-Sirajiyya, bab al-awl.',
        'input' => $female + ['heirs' => ['husband' => 1, 'daughters' => 2, 'father' => true, 'mother' => true]],
        'expect' => [
            'shares' => ['husband' => '3/15', 'daughter' => '8/15', 'father' => '2/15', 'mother' => '2/15'],
            'denominator' => 15,
            'awl' => true,
        ],
    ],
    [
        'id' => 'awl_12_to_17',
        'title' => 'Wife, mother, two uterine siblings and two full sisters — 12 goes to 17',
        'group' => 'awl',
        'source' => 'The largest awl on a base of twelve. al-Sirajiyya, bab al-awl.',
        'input' => $male + ['heirs' => ['wives' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['wife' => '3/17', 'mother' => '2/17', 'uterine_sibling' => '4/17', 'full_sister' => '8/17'],
            'denominator' => 17,
            'awl' => true,
        ],
    ],
];
