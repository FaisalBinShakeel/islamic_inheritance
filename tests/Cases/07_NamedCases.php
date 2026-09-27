<?php

declare(strict_types=1);

/**
 * The named doctrines: Umariyyatan, Mushtaraka and Akdariyya. These are where
 * a calculator that treats Faraid as a percentage split gives the wrong
 * answer, so each one is pinned down here, and the two that the schools
 * dispute are pinned down per school.
 */

$female = ['deceased_gender' => 'female'];
$male = ['deceased_gender' => 'male'];

return [
    [
        'id' => 'umariyyatan_husband',
        'title' => 'Umariyyatan with a husband: the mother takes a third of the remainder',
        'group' => 'named_case',
        'source' => "The two cases of Umar (al-Gharrawayn): husband 1/2, mother one third of what remains, father the rest. al-Sirajiyya, bab al-furud.",
        'input' => $female + ['madhhab' => 'hanafi', 'heirs' => ['husband' => 1, 'mother' => true, 'father' => true]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'father' => '1/3'],
            'denominator' => 6,
            'special_case' => 'umariyyatan',
        ],
    ],
    [
        'id' => 'umariyyatan_wife',
        'title' => 'Umariyyatan with a wife: the mother takes a quarter, not a third',
        'group' => 'named_case',
        'source' => 'The second of the two cases of Umar: wife 1/4, mother one third of the remaining 3/4, father the rest.',
        'input' => $male + ['madhhab' => 'hanafi', 'heirs' => ['wives' => 1, 'mother' => true, 'father' => true]],
        'expect' => [
            'shares' => ['wife' => '1/4', 'mother' => '1/4', 'father' => '1/2'],
            'denominator' => 4,
            'special_case' => 'umariyyatan',
        ],
    ],
    [
        'id' => 'umariyyatan_not_triggered_by_other_heirs',
        'title' => 'A fourth heir takes the case out of Umariyyatan',
        'group' => 'named_case',
        'source' => 'The doctrine applies only where the survivors are a spouse, the mother and the father and no one else.',
        'input' => $female + ['madhhab' => 'hanafi', 'heirs' => ['husband' => 1, 'mother' => true, 'father' => true, 'sons' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/4', 'mother' => '1/6', 'father' => '1/6', 'son' => '5/12'],
            'special_case' => null,
        ],
    ],
    [
        'id' => 'mushtaraka_hanafi',
        'title' => 'Mushtaraka (Hanafi): the full brothers take nothing',
        'group' => 'named_case',
        'source' => "Abu Hanifa follows Ali b. Abi Talib: the estate is exhausted by the fixed shares and the full brothers, as residuaries, take nothing. Ibn Rushd, Bidayat al-Mujtahid, kitab al-fara'id.",
        'input' => $female + ['madhhab' => 'hanafi', 'heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'uterine_sibling' => '1/3'],
            'excluded' => ['full_brother' => 'mushtaraka_full_siblings_take_nothing'],
            'special_case' => 'mushtaraka',
        ],
    ],
    [
        'id' => 'mushtaraka_shafii',
        'title' => "Mushtaraka (Shafi'i): the full brothers share the third",
        'group' => 'named_case',
        'source' => "Umar's ruling, followed by the Shafi'i school: the full brothers share the uterine siblings' third, counted per head. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + ['madhhab' => 'shafii', 'heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'uterine_sibling' => '1/6', 'full_brother' => '1/6'],
            'per_head' => ['uterine_sibling' => '1/12', 'full_brother' => '1/12'],
            'special_case' => 'mushtaraka',
        ],
    ],
    [
        'id' => 'mushtaraka_maliki',
        'title' => 'Mushtaraka (Maliki): the full brothers share the third',
        'group' => 'named_case',
        'source' => "Umar's ruling, followed by the Maliki school. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + ['madhhab' => 'maliki', 'heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'uterine_sibling' => '1/6', 'full_brother' => '1/6'],
            'special_case' => 'mushtaraka',
        ],
    ],
    [
        'id' => 'mushtaraka_hanbali_open_question',
        'title' => 'Mushtaraka (Hanbali): implemented as Ali\'s view, and flagged',
        'group' => 'named_case',
        'source' => "The classical Hanbali position follows Ali, as Hanafi does. The product specification groups Hanbali with Shafi'i instead; the engine implements the classical position and raises a warning rather than resolving the conflict silently. See docs/REVIEW-CHECKLIST.md.",
        'input' => $female + ['madhhab' => 'hanbali', 'heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'uterine_sibling' => '1/3'],
            'excluded' => ['full_brother' => 'mushtaraka_full_siblings_take_nothing'],
            'special_case' => 'mushtaraka',
            'warnings' => ['madhhab_hanbali_mushtaraka_under_review'],
        ],
    ],
    [
        'id' => 'akdariyya_shafii',
        'title' => "Al-Akdariyya (Shafi'i): denominator 27",
        'group' => 'named_case',
        'source' => "Zayd b. Thabit's resolution: the shares go to awl over nine, then the grandfather and the sister pool their portions and divide them two to one, giving 27. Ibn Rushd, Bidayat al-Mujtahid, kitab al-fara'id.",
        'input' => $female + ['madhhab' => 'shafii', 'heirs' => ['husband' => 1, 'mother' => true, 'paternal_grandfather' => true, 'full_sisters' => 1]],
        'expect' => [
            'shares' => [
                'husband' => '9/27',
                'mother' => '6/27',
                'paternal_grandfather' => '8/27',
                'full_sister' => '4/27',
            ],
            'denominator' => 27,
            'special_case' => 'akdariyya',
            'awl' => true,
        ],
    ],
    [
        'id' => 'akdariyya_maliki',
        'title' => 'Al-Akdariyya (Maliki): denominator 27',
        'group' => 'named_case',
        'source' => "Zayd b. Thabit's resolution, followed by the Maliki school. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + ['madhhab' => 'maliki', 'heirs' => ['husband' => 1, 'mother' => true, 'paternal_grandfather' => true, 'full_sisters' => 1]],
        'expect' => [
            'shares' => [
                'husband' => '9/27',
                'mother' => '6/27',
                'paternal_grandfather' => '8/27',
                'full_sister' => '4/27',
            ],
            'special_case' => 'akdariyya',
        ],
    ],
    [
        'id' => 'akdariyya_does_not_arise_in_hanafi',
        'title' => 'Al-Akdariyya cannot arise under Hanafi rules',
        'group' => 'named_case',
        'source' => 'Abu Hanifa: the grandfather excludes the sister outright, so the case never forms. al-Sirajiyya, bab al-jadd.',
        'input' => $female + ['madhhab' => 'hanafi', 'heirs' => ['husband' => 1, 'mother' => true, 'paternal_grandfather' => true, 'full_sisters' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/3', 'paternal_grandfather' => '1/6'],
            'excluded' => ['full_sister' => 'excluded_by_grandfather'],
            'special_case' => null,
        ],
    ],
];
