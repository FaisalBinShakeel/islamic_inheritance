<?php

declare(strict_types=1);

/**
 * Radd: the fixed shares leave a surplus and no residuary survives, so the
 * surplus returns to the fixed-share heirs in proportion to their shares —
 * and, in the Hanafi school, never to a spouse.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'radd_mother_and_daughter',
        'title' => 'Mother and one daughter',
        'group' => 'radd',
        'source' => 'Shares of 1/6 and 1/2 leave a third; returned in the ratio 1:3. al-Sirajiyya, bab al-radd.',
        'input' => $male + ['heirs' => ['mother' => true, 'daughters' => 1]],
        'expect' => ['shares' => ['mother' => '1/4', 'daughter' => '3/4'], 'radd' => true, 'denominator' => 4],
    ],
    [
        'id' => 'radd_mother_and_two_daughters',
        'title' => 'Mother and two daughters',
        'group' => 'radd',
        'source' => 'Shares of 1/6 and 2/3 leave a sixth; returned in the ratio 1:4. al-Sirajiyya, bab al-radd.',
        'input' => $male + ['heirs' => ['mother' => true, 'daughters' => 2]],
        'expect' => ['shares' => ['mother' => '1/5', 'daughter' => '4/5'], 'radd' => true, 'denominator' => 5],
    ],
    [
        'id' => 'radd_daughter_and_sons_daughter',
        'title' => "Daughter and son's daughter",
        'group' => 'radd',
        'source' => "1/2 and the completing 1/6 leave a third; returned in the ratio 3:1. al-Sirajiyya, bab al-radd.",
        'input' => $male + ['heirs' => ['daughters' => 1, 'sons_daughters' => 1]],
        'expect' => ['shares' => ['daughter' => '3/4', 'sons_daughter' => '1/4'], 'radd' => true],
    ],
    [
        'id' => 'radd_husband_and_mother',
        'title' => 'Husband and mother: the surplus goes only to the mother',
        'group' => 'radd',
        'source' => 'Hanafi radd excludes the spouse; the mother takes her third and the whole return. al-Sirajiyya, bab al-radd.',
        'input' => $female + ['heirs' => ['husband' => 1, 'mother' => true]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/2'],
            'radd' => true,
            'warnings' => ['radd_excludes_spouse'],
        ],
    ],
    [
        'id' => 'radd_wife_mother_daughter',
        'title' => 'Wife, mother and daughter: the wife keeps her eighth only',
        'group' => 'radd',
        'source' => 'The wife takes 1/8 off the top; the remaining 7/8 is returned between mother and daughter in the ratio 1:3. al-Sirajiyya, bab al-radd.',
        'input' => $male + ['heirs' => ['wives' => 1, 'mother' => true, 'daughters' => 1]],
        'expect' => [
            'shares' => ['wife' => '1/8', 'mother' => '7/32', 'daughter' => '21/32'],
            'radd' => true,
            'denominator' => 32,
        ],
    ],
    [
        'id' => 'radd_grandmother_and_uterine_sibling',
        'title' => 'Grandmother and one uterine sibling share equally after radd',
        'group' => 'radd',
        'source' => 'Two equal sixths, so the return is equal. al-Sirajiyya, bab al-radd.',
        'input' => $male + ['heirs' => ['maternal_grandmother' => true, 'uterine_siblings' => 1]],
        'expect' => [
            'shares' => ['maternal_grandmother' => '1/2', 'uterine_sibling' => '1/2'],
            'radd' => true,
        ],
    ],
    [
        'id' => 'radd_mother_and_full_sister',
        'title' => 'Mother and one full sister',
        'group' => 'radd',
        'source' => 'A single sibling leaves the mother on a third; 1/3 and 1/2 are returned in the ratio 2:3. al-Sirajiyya, bab al-radd.',
        'input' => $male + ['heirs' => ['mother' => true, 'full_sisters' => 1]],
        'expect' => ['shares' => ['mother' => '2/5', 'full_sister' => '3/5'], 'radd' => true],
    ],
];
