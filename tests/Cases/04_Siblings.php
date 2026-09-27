<?php

declare(strict_types=1);

/**
 * Siblings: the three kinds, the sister who becomes residuary beside a
 * daughter, the consanguine sister's completing sixth, and the uterine
 * siblings who share equally whatever their sex.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$female = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'sister_asaba_maal_ghayr_with_daughter',
        'title' => 'Full sister becomes residuary beside a daughter',
        'group' => 'siblings',
        'source' => "Ruling of Ibn Mas'ud and the established asaba ma'a'l-ghayr: sisters with daughters are residuaries. al-Sirajiyya, bab al-asabat.",
        'input' => $male + ['heirs' => ['daughters' => 1, 'full_sisters' => 1]],
        'expect' => ['shares' => ['daughter' => '1/2', 'full_sister' => '1/2'], 'denominator' => 2],
    ],
    [
        'id' => 'sisters_asaba_maal_ghayr_with_two_daughters',
        'title' => 'Two full sisters take the residue beside two daughters',
        'group' => 'siblings',
        'source' => "Asaba ma'a'l-ghayr; the daughters take two thirds and the sisters share what is left.",
        'input' => $male + ['heirs' => ['daughters' => 2, 'full_sisters' => 2]],
        'expect' => [
            'shares' => ['daughter' => '2/3', 'full_sister' => '1/3'],
            'per_head' => ['full_sister' => '1/6'],
        ],
    ],
    [
        'id' => 'maal_ghayr_sister_excludes_consanguine',
        'title' => 'A full sister made residuary excludes the consanguine sister',
        'group' => 'siblings',
        'source' => "Once the full sister is asaba ma'a'l-ghayr she stands where a full brother would, and the consanguine siblings are excluded. al-Sirajiyya, bab al-hajb.",
        'input' => $male + ['heirs' => ['daughters' => 1, 'full_sisters' => 1, 'consanguine_sisters' => 1]],
        'expect' => [
            'shares' => ['daughter' => '1/2', 'full_sister' => '1/2'],
            'excluded' => ['consanguine_sister' => 'excluded_by_full_sister_as_residuary'],
        ],
    ],
    [
        'id' => 'consanguine_sister_completes_two_thirds',
        'title' => 'Consanguine sister takes the sixth completing the two thirds',
        'group' => 'siblings',
        'source' => "By analogy with the son's daughter completing the daughters' two thirds. al-Sirajiyya, bab al-furud.",
        'input' => $male + ['heirs' => ['full_sisters' => 1, 'consanguine_sisters' => 1, 'paternal_uncles' => 1]],
        'expect' => [
            'shares' => ['full_sister' => '1/2', 'consanguine_sister' => '1/6', 'full_paternal_uncle' => '1/3'],
        ],
    ],
    [
        'id' => 'two_full_sisters_exclude_consanguine_sister',
        'title' => 'Two full sisters exclude the consanguine sister',
        'group' => 'siblings',
        'source' => 'The two thirds is exhausted by the full sisters. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['full_sisters' => 2, 'consanguine_sisters' => 1, 'paternal_uncles' => 1]],
        'expect' => [
            'shares' => ['full_sister' => '2/3', 'full_paternal_uncle' => '1/3'],
            'excluded' => ['consanguine_sister' => 'excluded_by_two_full_sisters'],
        ],
    ],
    [
        'id' => 'consanguine_brother_rescues_consanguine_sister',
        'title' => 'A consanguine brother makes his sister residuary instead of excluded',
        'group' => 'siblings',
        'source' => "Asaba bi'l-ghayr: the brother turns his sister into a residuary at two to one. al-Sirajiyya, bab al-asabat.",
        'input' => $male + ['heirs' => ['full_sisters' => 2, 'consanguine_sisters' => 1, 'consanguine_brothers' => 1]],
        'expect' => [
            'shares' => ['full_sister' => '2/3', 'consanguine_brother' => '2/9', 'consanguine_sister' => '1/9'],
            'denominator' => 9,
        ],
    ],
    [
        'id' => 'uterine_siblings_share_equally',
        'title' => 'Uterine siblings share the third equally, male and female alike',
        'group' => 'siblings',
        'source' => "Qur'an 4:12: fa-hum shuraka'u fi'l-thuluth — they are partners in the third, without the two-to-one rule.",
        'input' => $female + ['heirs' => ['husband' => 1, 'uterine_siblings' => 2, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/2', 'uterine_sibling' => '1/3', 'full_brother' => '1/6'],
            'per_head' => ['uterine_sibling' => '1/6'],
            'denominator' => 6,
        ],
    ],
    [
        'id' => 'full_brother_and_sister_two_to_one',
        'title' => 'Full brother and sister take the residue two to one',
        'group' => 'siblings',
        'source' => "Qur'an 4:176: the brother takes twice the sister's share.",
        'input' => $female + ['heirs' => ['husband' => 1, 'full_brothers' => 1, 'full_sisters' => 1]],
        'expect' => ['shares' => ['husband' => '1/2', 'full_brother' => '1/3', 'full_sister' => '1/6']],
    ],
    [
        'id' => 'mother_reduced_by_two_siblings',
        'title' => 'Two siblings reduce the mother to a sixth',
        'group' => 'siblings',
        'source' => "Qur'an 4:11: fa-in kana lahu ikhwatun fa-li-ummihi al-sudus.",
        'input' => $male + ['heirs' => ['mother' => true, 'father' => true, 'full_brothers' => 2]],
        'expect' => [
            'shares' => ['mother' => '1/6', 'father' => '5/6'],
            'excluded' => ['full_brother' => 'excluded_by_father'],
        ],
    ],
    [
        'id' => 'mother_reduced_by_siblings_the_father_excludes',
        'title' => 'Siblings excluded by the father still reduce the mother',
        'group' => 'siblings',
        'source' => 'Hajb nuqsan works by presence, not by inheritance: the excluded siblings still hold the mother to a sixth. al-Sirajiyya, bab al-hajb.',
        'input' => $male + ['heirs' => ['mother' => true, 'father' => true, 'full_sisters' => 3]],
        'expect' => [
            'shares' => ['mother' => '1/6', 'father' => '5/6'],
            'excluded' => ['full_sister' => 'excluded_by_father'],
        ],
    ],
    [
        'id' => 'mother_one_third_with_one_sibling',
        'title' => 'A single sibling does not reduce the mother',
        'group' => 'siblings',
        'source' => "Qur'an 4:11 requires ikhwa — two or more — before the mother falls to a sixth.",
        'input' => $male + ['heirs' => ['mother' => true, 'father' => true, 'full_brothers' => 1]],
        'expect' => [
            'shares' => ['mother' => '1/3', 'father' => '2/3'],
            'excluded' => ['full_brother' => 'excluded_by_father'],
        ],
    ],
];
