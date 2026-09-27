<?php

declare(strict_types=1);

/**
 * The grandfather competing with brothers — the single largest divergence
 * between the schools, and the reason the engine takes the madhhab as a
 * required parameter rather than assuming one.
 *
 * Hanafi (Abu Hanifa): the grandfather excludes them.
 * Maliki, Shafi'i, Hanbali: Zayd b. Thabit's doctrine — the grandfather takes
 * whichever is best for him of sharing as a brother (muqasama), one third of
 * the residue, or one sixth of the whole estate.
 */

$male = ['deceased_gender' => 'male'];
$female = ['deceased_gender' => 'female'];

return [
    [
        'id' => 'hanafi_grandfather_excludes_brothers',
        'title' => 'Hanafi: the grandfather excludes the full brothers',
        'group' => 'madhhab',
        'source' => "Abu Hanifa's view, reported with the position of Abu Bakr: the grandfather stands in the father's place and excludes the brothers. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $male + ['madhhab' => 'hanafi', 'heirs' => ['paternal_grandfather' => true, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1'],
            'excluded' => ['full_brother' => 'excluded_by_grandfather'],
        ],
    ],
    [
        'id' => 'shafii_grandfather_muqasama',
        'title' => "Shafi'i: muqasama — the grandfather shares as one of the brothers",
        'group' => 'madhhab',
        'source' => "Zayd b. Thabit's doctrine. With two brothers, muqasama gives the grandfather one third, the same as a third of the estate, so he takes a third and the brothers two thirds. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $male + ['madhhab' => 'shafii', 'heirs' => ['paternal_grandfather' => true, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1/3', 'full_brother' => '2/3'],
            'per_head' => ['full_brother' => '1/3'],
            'warnings' => ['zayd_grandfather_doctrine_applied'],
        ],
    ],
    [
        'id' => 'maliki_grandfather_one_third_of_residue',
        'title' => 'Maliki: with four brothers the grandfather falls back on a third',
        'group' => 'madhhab',
        'source' => "Zayd b. Thabit's doctrine: muqasama with four brothers would give the grandfather one fifth, so he takes one third instead. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $male + ['madhhab' => 'maliki', 'heirs' => ['paternal_grandfather' => true, 'full_brothers' => 4]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1/3', 'full_brother' => '2/3'],
            'per_head' => ['full_brother' => '1/6'],
        ],
    ],
    [
        'id' => 'shafii_grandfather_with_a_sharer',
        'title' => "Shafi'i: husband, grandfather and two brothers",
        'group' => 'madhhab',
        'source' => "The husband takes one half; of the remaining half, muqasama, one third of the residue and one sixth of the estate all come to one sixth, so the grandfather takes one sixth. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + ['madhhab' => 'shafii', 'heirs' => ['husband' => 1, 'paternal_grandfather' => true, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'paternal_grandfather' => '1/6', 'full_brother' => '1/3'],
            'denominator' => 6,
        ],
    ],
    [
        'id' => 'hanbali_grandfather_one_sixth_of_estate',
        'title' => 'Hanbali: the guaranteed sixth of the whole estate wins',
        'group' => 'madhhab',
        'source' => "Zayd b. Thabit's doctrine: with a half and a sixth already taken by sharers and four brothers competing, both muqasama (1/15) and a third of the residue (1/9) fall below the grandfather's guaranteed sixth. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + [
            'madhhab' => 'hanbali',
            'heirs' => ['husband' => 1, 'mother' => true, 'paternal_grandfather' => true, 'full_brothers' => 4],
        ],
        'expect' => [
            'shares' => [
                'husband' => '1/2', 'mother' => '1/6',
                'paternal_grandfather' => '1/6', 'full_brother' => '1/6',
            ],
            'per_head' => ['full_brother' => '1/24'],
            'denominator' => 6,
        ],
    ],
    [
        'id' => 'shafii_grandfather_with_brothers_and_sisters',
        'title' => "Shafi'i: the grandfather shares with a brother and a sister at two to one",
        'group' => 'madhhab',
        'source' => "Muqasama counts the grandfather as a brother: two shares for him, two for the brother, one for the sister. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $male + [
            'madhhab' => 'shafii',
            'heirs' => ['paternal_grandfather' => true, 'full_brothers' => 1, 'full_sisters' => 1],
        ],
        'expect' => [
            'shares' => ['paternal_grandfather' => '2/5', 'full_brother' => '2/5', 'full_sister' => '1/5'],
            'denominator' => 5,
        ],
    ],
    [
        'id' => 'hanafi_grandfather_excludes_sisters',
        'title' => 'Hanafi: the grandfather excludes the full sisters as well',
        'group' => 'madhhab',
        'source' => "Abu Hanifa's view: the grandfather stands in the father's place, and the father excludes the siblings of both sexes. al-Sirajiyya, bab al-jadd.",
        'input' => $male + ['madhhab' => 'hanafi', 'heirs' => ['paternal_grandfather' => true, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1'],
            'excluded' => ['full_sister' => 'excluded_by_grandfather'],
        ],
    ],
    [
        'id' => 'shafii_grandfather_with_sisters_only',
        'title' => "Shafi'i: sisters beside the grandfather become residuaries, not sharers",
        'group' => 'madhhab',
        'source' => "Zayd b. Thabit's doctrine: a sister with the grandfather takes as a residuary in the muqasama rather than her Quranic two thirds. Muqasama over four shares gives the grandfather one half. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $male + ['madhhab' => 'shafii', 'heirs' => ['paternal_grandfather' => true, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1/2', 'full_sister' => '1/2'],
            'per_head' => ['full_sister' => '1/4'],
            'warnings' => ['zayd_grandfather_doctrine_applied'],
        ],
    ],
    [
        'id' => 'shafii_grandfather_sister_and_husband',
        'title' => "Shafi'i: husband, grandfather and one sister (no mother, so not Akdariyya)",
        'group' => 'madhhab',
        'source' => "Zayd b. Thabit's doctrine outside al-Akdariyya: the husband takes one half, and of the residue muqasama gives the grandfather two thirds and the sister one third. Ibn Rushd, Bidayat al-Mujtahid.",
        'input' => $female + [
            'madhhab' => 'shafii',
            'heirs' => ['husband' => 1, 'paternal_grandfather' => true, 'full_sisters' => 1],
        ],
        'expect' => [
            'shares' => ['husband' => '1/2', 'paternal_grandfather' => '1/3', 'full_sister' => '1/6'],
            'denominator' => 6,
            'special_case' => null,
        ],
    ],
    [
        'id' => 'madhhab_must_be_supplied',
        'title' => 'The engine refuses to guess a school',
        'group' => 'madhhab',
        'source' => 'Product requirement: the madhhab is a required engine parameter with no default.',
        'input' => $male + ['heirs' => ['sons' => 1]],
        'expect_error' => 'madhhab must be supplied explicitly',
    ],
    [
        'id' => 'unknown_madhhab_rejected',
        'title' => 'An unknown school is rejected rather than silently defaulted',
        'group' => 'madhhab',
        'source' => 'Product requirement: v1 covers the four Sunni schools only; Ja\'fari rules are out of scope.',
        'input' => $male + ['madhhab' => 'jafari', 'heirs' => ['sons' => 1]],
        'expect_error' => 'Unknown madhhab',
    ],
];
