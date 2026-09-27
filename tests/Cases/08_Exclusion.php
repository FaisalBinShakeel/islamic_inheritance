<?php

declare(strict_types=1);

/**
 * Hajb hirman — full exclusion chains. Relatives always ask who shut them out,
 * so every one of these asserts the reason as well as the outcome.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];

return [
    [
        'id' => 'son_excludes_full_brother',
        'title' => 'A son excludes the full brother',
        'group' => 'exclusion',
        'source' => 'A male descendant excludes all siblings. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['sons' => 1, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['son' => '1'],
            'excluded' => ['full_brother' => 'excluded_by_male_descendant'],
        ],
    ],
    [
        'id' => 'father_excludes_grandfather',
        'title' => 'The father excludes the true grandfather',
        'group' => 'exclusion',
        'source' => 'The nearer ascendant excludes the further. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['sons' => 1, 'father' => true, 'paternal_grandfather' => true]],
        'expect' => [
            'shares' => ['father' => '1/6', 'son' => '5/6'],
            'excluded' => ['paternal_grandfather' => 'excluded_by_father'],
            'warnings' => ['grandfather_present_but_excluded_by_father'],
        ],
    ],
    [
        'id' => 'son_excludes_sons_son',
        'title' => "A son excludes the son's son",
        'group' => 'exclusion',
        'source' => 'The nearer agnatic descendant excludes the further. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['sons' => 1, 'sons_sons' => 1]],
        'expect' => [
            'shares' => ['son' => '1'],
            'excluded' => ['sons_son' => 'excluded_by_son'],
        ],
    ],
    [
        'id' => 'mother_excludes_both_grandmothers',
        'title' => 'The mother excludes every true grandmother',
        'group' => 'exclusion',
        'source' => 'The mother excludes the grandmothers on both sides. al-Sirajiyya, bab al-hajb.',
        'input' => $male + [
            'heirs' => [
                'sons' => 1, 'mother' => true,
                'maternal_grandmother' => true, 'paternal_grandmother' => true,
            ],
        ],
        'expect' => [
            'shares' => ['mother' => '1/6', 'son' => '5/6'],
            'excluded' => [
                'maternal_grandmother' => 'excluded_by_mother',
                'paternal_grandmother' => 'excluded_by_mother',
            ],
        ],
    ],
    [
        'id' => 'father_excludes_paternal_grandmother_only',
        'title' => 'The father excludes his own mother, not the maternal grandmother',
        'group' => 'exclusion',
        'source' => 'The paternal grandmother is excluded by the father; the maternal grandmother is not. al-Sirajiyya, bab al-hajb.',
        'input' => $male + [
            'heirs' => ['father' => true, 'paternal_grandmother' => true, 'maternal_grandmother' => true],
        ],
        'expect' => [
            'shares' => ['maternal_grandmother' => '1/6', 'father' => '5/6'],
            'excluded' => ['paternal_grandmother' => 'excluded_by_father'],
        ],
    ],
    [
        'id' => 'full_brother_excludes_consanguine_brother',
        'title' => 'The full brother excludes the consanguine brother',
        'group' => 'exclusion',
        'source' => 'Within a class of asaba, the stronger kinship excludes the weaker. al-Sirajiyya, bab al-asabat.',
        'input' => $male + ['heirs' => ['full_brothers' => 1, 'consanguine_brothers' => 1]],
        'expect' => [
            'shares' => ['full_brother' => '1'],
            'excluded' => ['consanguine_brother' => 'excluded_by_full_brother'],
        ],
    ],
    [
        'id' => 'daughter_excludes_uterine_siblings',
        'title' => 'A daughter excludes the uterine siblings',
        'group' => 'exclusion',
        'source' => "Qur'an 4:12: the uterine siblings inherit only from one who leaves no child and no parent. al-Sirajiyya, bab al-hajb.",
        'input' => $male + ['heirs' => ['daughters' => 1, 'uterine_siblings' => 2, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['daughter' => '1/2', 'full_brother' => '1/2'],
            'excluded' => ['uterine_sibling' => 'excluded_by_descendant'],
        ],
    ],
    [
        'id' => 'grandfather_excludes_uterine_siblings',
        'title' => 'The true grandfather excludes the uterine siblings',
        'group' => 'exclusion',
        'source' => 'The true grandfather blocks the uterine siblings in every school. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['paternal_grandfather' => true, 'uterine_siblings' => 2]],
        'expect' => [
            'shares' => ['paternal_grandfather' => '1'],
            'excluded' => ['uterine_sibling' => 'excluded_by_grandfather'],
        ],
    ],
    [
        'id' => 'nearer_asaba_excludes_uncle',
        'title' => 'The full brother excludes the paternal uncle',
        'group' => 'exclusion',
        'source' => "A nearer class of asaba excludes a further one: the father's descendants come before the grandfather's. al-Sirajiyya, bab al-asabat.",
        'input' => $male + ['heirs' => ['full_brothers' => 1, 'paternal_uncles' => 1, 'paternal_uncle_sons' => 1]],
        'expect' => [
            'shares' => ['full_brother' => '1'],
            'excluded' => [
                'full_paternal_uncle' => 'excluded_by_full_brother',
                'full_paternal_uncle_son' => 'excluded_by_full_brother',
            ],
        ],
    ],
    [
        'id' => 'disqualified_son_by_homicide',
        'title' => 'A son disqualified for homicide does not inherit and does not block',
        'group' => 'exclusion',
        'source' => 'Hadith: "the killer does not inherit" (Ibn Majah, Tirmidhi). The disqualified heir is treated as if he did not survive.',
        'input' => $male + [
            'heirs' => ['sons' => 1, 'daughters' => 1, 'mother' => true],
            'disqualified' => [['heir' => 'sons', 'reason' => 'homicide']],
        ],
        'expect' => [
            'shares' => ['mother' => '1/4', 'daughter' => '3/4'],
            'excluded' => ['son' => 'disqualified_homicide'],
            'radd' => true,
        ],
    ],
    [
        'id' => 'disqualified_by_religion',
        'title' => 'A non-Muslim heir is excluded under the classical rule',
        'group' => 'exclusion',
        'source' => 'Hadith: "the Muslim does not inherit from the disbeliever, nor the disbeliever from the Muslim" (al-Bukhari, Muslim). Offered as an explicit per-heir flag, defaulting to off.',
        'input' => $male + [
            'heirs' => ['sons' => 2, 'mother' => true],
            'disqualified' => [['heir' => 'sons', 'reason' => 'different_religion', 'count' => 1]],
        ],
        'expect' => [
            'shares' => ['mother' => '1/6', 'son' => '5/6'],
            'excluded' => ['son' => 'disqualified_different_religion'],
        ],
    ],
];
