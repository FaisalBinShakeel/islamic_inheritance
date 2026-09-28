<?php

declare(strict_types=1);

/**
 * The Ahl-e-Hadith / Ghair Muqallid option.
 *
 * Inheritance is mostly explicit text, so on ordinary estates this produces
 * exactly what all four schools produce. These cases pin down the handful of
 * points where the choice of position actually changes the figures, so that a
 * reviewer can confirm or correct each one individually.
 *
 * EVERY expected answer here rests on an attribution that has NOT been
 * verified by anyone qualified in this position. The `source` field names the
 * basis the implementation rests on; it does not claim a fatwa.
 */

$male = ['madhhab' => 'ahl_e_hadith', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'ahl_e_hadith', 'deceased_gender' => 'female'];

$pending = ' ATTRIBUTION NOT VERIFIED — see docs/REVIEW-CHECKLIST.md.';

return [
    [
        'id' => 'ahl_e_hadith_ordinary_estate_matches_the_schools',
        'title' => 'An ordinary estate comes out the same as all four schools',
        'group' => 'ahl_e_hadith',
        'source' => "Qur'an 4:11-12. The fractions are explicit text, so there is nothing here for the schools to differ over.",
        'input' => $male + ['heirs' => ['wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3]],
        'expect' => [
            'shares' => ['wife' => '1/8', 'mother' => '1/6', 'son' => '17/42', 'daughter' => '17/56'],
            'denominator' => 168,
        ],
    ],
    [
        'id' => 'ahl_e_hadith_grandfather_excludes_brothers',
        'title' => 'The grandfather excludes the brothers',
        'group' => 'ahl_e_hadith',
        'source' => 'The position reported from Abu Bakr and from Ibn Abbas, that the grandfather stands in the father\'s place, against Zayd b. Thabit\'s muqasama. Both views are set out in Ibn Rushd, Bidayat al-Mujtahid, kitab al-fara\'id.' . $pending,
        'input' => $male + ['heirs' => ['paternal_grandfather' => true, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1'],
            'excluded' => ['full_brother' => 'excluded_by_grandfather'],
        ],
    ],
    [
        'id' => 'ahl_e_hadith_akdariyya_does_not_arise',
        'title' => 'Al-Akdariyya cannot arise, as in the Hanafi school',
        'group' => 'ahl_e_hadith',
        'source' => 'Follows from the grandfather excluding the sister: the case never forms.' . $pending,
        'input' => $female + ['heirs' => ['husband' => 1, 'mother' => true, 'paternal_grandfather' => true, 'full_sisters' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/3', 'paternal_grandfather' => '1/6'],
            'excluded' => ['full_sister' => 'excluded_by_grandfather'],
            'special_case' => null,
        ],
    ],
    [
        'id' => 'ahl_e_hadith_mushtaraka_full_siblings_take_nothing',
        'title' => 'Mushtaraka: the full brothers take nothing',
        'group' => 'ahl_e_hadith',
        'source' => "The position of Ali b. Abi Talib, against the sharing view reported from Umar. Both are in Ibn Rushd, Bidayat al-Mujtahid." . $pending,
        'input' => $female + ['heirs' => ['husband' => 1, 'mother' => true, 'uterine_siblings' => 2, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'mother' => '1/6', 'uterine_sibling' => '1/3'],
            'excluded' => ['full_brother' => 'mushtaraka_full_siblings_take_nothing'],
            'special_case' => 'mushtaraka',
        ],
    ],
    [
        'id' => 'ahl_e_hadith_sole_husband_does_not_take_the_surplus',
        'title' => 'A sole surviving husband still takes only his half',
        'group' => 'ahl_e_hadith',
        'source' => 'CORRECTED from the opposite answer. Ibn Qudamah, al-Mughni 6/186, quoted at islamqa.info/en/answers/160948: the surplus is not given to a spouse, by consensus, and the contrary report from Uthman is explained there as a payment made on another basis. See docs/SOURCES-CONSULTED.md.',
        'input' => $female + ['heirs' => ['husband' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/2'],
            'undistributed' => '1/2',
            'radd' => false,
            'warnings' => ['surplus_undistributed_spouse_excluded_from_radd'],
        ],
    ],
    [
        'id' => 'ahl_e_hadith_sole_wife_does_not_take_the_surplus',
        'title' => 'A sole surviving wife still takes only her quarter',
        'group' => 'ahl_e_hadith',
        'source' => 'Same source as the husband case above.',
        'input' => $male + ['heirs' => ['wives' => 1]],
        'expect' => ['shares' => ['wife' => '1/4'], 'undistributed' => '3/4', 'radd' => false],
    ],
    [
        'id' => 'ahl_e_hadith_radd_still_passes_over_a_spouse_when_others_survive',
        'title' => 'A spouse still takes no part in radd when another heir survives',
        'group' => 'ahl_e_hadith',
        'source' => 'Ibn Qudamah, al-Mughni 6/186: the surplus returns to the fixed-share heirs, except in the case of a husband or wife. Quoted at islamqa.info/en/answers/160948.',
        'input' => $male + ['heirs' => ['wives' => 1, 'daughters' => 1]],
        'expect' => ['shares' => ['wife' => '1/8', 'daughter' => '7/8'], 'radd' => true],
    ],
    [
        'id' => 'ahl_e_hadith_awl_is_applied_as_the_majority_does',
        'title' => "Awl is applied; Ibn Abbas's rejection of it is not implemented",
        'group' => 'ahl_e_hadith',
        'source' => "Ibn Abbas rejected awl and would have placed the whole reduction on the daughters rather than spreading it. That minority view is documented in Ibn Rushd, Bidayat al-Mujtahid, and is deliberately NOT implemented here; this case records that decision so a reviewer can overturn it." . $pending,
        'input' => $male + ['heirs' => ['wives' => 1, 'daughters' => 2, 'father' => true, 'mother' => true]],
        'expect' => [
            'shares' => ['wife' => '3/27', 'daughter' => '16/27', 'father' => '4/27', 'mother' => '4/27'],
            'denominator' => 27,
            'awl' => true,
            'warnings' => ['madhhab_ahl_e_hadith_awl_minority_view_not_applied'],
        ],
    ],
];
