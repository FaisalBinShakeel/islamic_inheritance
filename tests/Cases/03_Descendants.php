<?php

declare(strict_types=1);

/**
 * Children and agnatic grandchildren: the 2:1 residuary split, the son's
 * daughter completing the two thirds, and her exclusion by two daughters.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];

return [
    [
        'id' => 'sons_and_daughters_two_to_one',
        'title' => 'Two sons and three daughters take the residue two to one',
        'group' => 'descendants',
        'source' => "Qur'an 4:11: li'l-dhakari mithlu hazzi'l-unthayayn.",
        'input' => $male + ['heirs' => ['wives' => 1, 'mother' => true, 'sons' => 2, 'daughters' => 3]],
        'expect' => [
            'shares' => ['wife' => '1/8', 'mother' => '1/6', 'son' => '17/42', 'daughter' => '17/56'],
            'per_head' => ['son' => '17/84', 'daughter' => '17/168'],
            'denominator' => 168,
        ],
    ],
    [
        'id' => 'sons_daughter_completes_two_thirds',
        'title' => "One daughter and a son's daughter: the sixth that completes the two thirds",
        'group' => 'descendants',
        'source' => "Ruling of Ibn Mas'ud reported by al-Bukhari: for the daughter one half, for the son's daughter one sixth completing the two thirds, and the rest to the brother.",
        'input' => $male + ['heirs' => ['daughters' => 1, 'sons_daughters' => 1, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['daughter' => '1/2', 'sons_daughter' => '1/6', 'full_brother' => '1/3'],
            'denominator' => 6,
        ],
    ],
    [
        'id' => 'sons_daughters_excluded_by_two_daughters',
        'title' => "Two daughters exclude the son's daughter",
        'group' => 'descendants',
        'source' => "The two thirds reserved for female descendants is already taken by the daughters. al-Sirajiyya, bab al-hajb.",
        'input' => $male + ['heirs' => ['daughters' => 2, 'sons_daughters' => 1, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['daughter' => '2/3', 'full_brother' => '1/3'],
            'excluded' => ['sons_daughter' => 'excluded_by_two_daughters'],
        ],
    ],
    [
        'id' => 'sons_son_rescues_sons_daughter',
        'title' => "A son's son at her level makes the son's daughter residuary",
        'group' => 'descendants',
        'source' => "Asaba bi'l-ghayr: the son's daughter who would otherwise be excluded by two daughters becomes residuary with an agnatic grandson of her own degree. al-Sirajiyya, bab al-asabat.",
        'input' => $male + ['heirs' => ['daughters' => 2, 'sons_daughters' => 1, 'sons_sons' => 1]],
        'expect' => [
            'shares' => ['daughter' => '2/3', 'sons_son' => '2/9', 'sons_daughter' => '1/9'],
            'denominator' => 9,
        ],
    ],
    [
        'id' => 'sons_daughters_two_thirds',
        'title' => "Two son's daughters take two thirds when no daughter survives",
        'group' => 'descendants',
        'source' => "The son's daughters stand in the daughters' place in their absence. al-Sirajiyya, bab al-furud.",
        'input' => $male + ['heirs' => ['sons_daughters' => 2, 'full_brothers' => 1]],
        'expect' => ['shares' => ['sons_daughter' => '2/3', 'full_brother' => '1/3']],
    ],
    [
        'id' => 'father_one_sixth_plus_residue',
        'title' => 'Father takes a sixth and the residue when only daughters survive',
        'group' => 'descendants',
        'source' => "Qur'an 4:11: the father's sixth as a sharer, and the remainder as asaba once the daughters have taken their two thirds.",
        'input' => $male + ['heirs' => ['daughters' => 2, 'father' => true]],
        'expect' => [
            'shares' => ['daughter' => '2/3', 'father' => '1/3'],
            'denominator' => 3,
        ],
    ],
    [
        'id' => 'father_one_sixth_only_with_son',
        'title' => 'Father is held to a sixth by a son',
        'group' => 'descendants',
        'source' => "Qur'an 4:11: one sixth for each parent where the deceased left a child; the son takes the residue.",
        'input' => $male + ['heirs' => ['sons' => 1, 'father' => true, 'mother' => true]],
        'expect' => ['shares' => ['father' => '1/6', 'mother' => '1/6', 'son' => '2/3']],
    ],
];
