<?php

declare(strict_types=1);

/**
 * Every heir class standing alone.
 *
 * These are the cases that catch a rule table transcribed wrongly, and they
 * also pin down what the engine does with a surplus: under Hanafi radd the
 * single non-spousal heir takes the whole estate, while a lone spouse takes
 * only their fixed share and the remainder is reported as undistributed
 * rather than invented away.
 */

$hanafi = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];
$hanafiFemaleDeceased = ['madhhab' => 'hanafi', 'deceased_gender' => 'female'];

return [
    [
        'id' => 'single_husband',
        'title' => 'Husband alone, no descendant',
        'group' => 'single_heir',
        'source' => "Qur'an 4:12 (husband takes one half where there is no child); al-Sirajiyya, bab al-furud. Radd does not reach a spouse: al-Sirajiyya, bab al-radd.",
        'input' => $hanafiFemaleDeceased + ['heirs' => ['husband' => 1]],
        'expect' => [
            'shares' => ['husband' => '1/2'],
            'undistributed' => '1/2',
            'radd' => false,
            'warnings' => ['surplus_undistributed_spouse_excluded_from_radd'],
        ],
    ],
    [
        'id' => 'single_wife',
        'title' => 'One wife alone, no descendant',
        'group' => 'single_heir',
        'source' => "Qur'an 4:12 (wife takes one quarter where there is no child); al-Sirajiyya, bab al-furud.",
        'input' => $hanafi + ['heirs' => ['wives' => 1]],
        'expect' => [
            'shares' => ['wife' => '1/4'],
            'undistributed' => '3/4',
            'radd' => false,
        ],
    ],
    [
        'id' => 'single_father',
        'title' => 'Father alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11; with no descendant the father inherits as asaba and takes the whole estate. al-Sirajiyya, bab al-asabat.",
        'input' => $hanafi + ['heirs' => ['father' => true]],
        'expect' => ['shares' => ['father' => '1'], 'denominator' => 1],
    ],
    [
        'id' => 'single_mother',
        'title' => 'Mother alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11 (one third with no child and fewer than two siblings), then radd of the surplus. al-Sirajiyya, bab al-radd.",
        'input' => $hanafi + ['heirs' => ['mother' => true]],
        'expect' => ['shares' => ['mother' => '1'], 'radd' => true],
    ],
    [
        'id' => 'single_son',
        'title' => 'One son alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11; the son is the nearest asaba and takes the whole residue.",
        'input' => $hanafi + ['heirs' => ['sons' => 1]],
        'expect' => ['shares' => ['son' => '1']],
    ],
    [
        'id' => 'two_sons_alone',
        'title' => 'Two sons alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11; asaba share equally where all are male.",
        'input' => $hanafi + ['heirs' => ['sons' => 2]],
        'expect' => ['shares' => ['son' => '1'], 'per_head' => ['son' => '1/2']],
    ],
    [
        'id' => 'single_daughter',
        'title' => 'One daughter alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11 (one half for a single daughter), then radd. al-Sirajiyya, bab al-radd.",
        'input' => $hanafi + ['heirs' => ['daughters' => 1]],
        'expect' => ['shares' => ['daughter' => '1'], 'radd' => true],
    ],
    [
        'id' => 'two_daughters_alone',
        'title' => 'Two daughters alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:11 (two thirds for two or more daughters), then radd.",
        'input' => $hanafi + ['heirs' => ['daughters' => 2]],
        'expect' => ['shares' => ['daughter' => '1'], 'per_head' => ['daughter' => '1/2'], 'radd' => true],
    ],
    [
        'id' => 'single_paternal_grandfather',
        'title' => 'True grandfather alone',
        'group' => 'single_heir',
        'source' => 'The true grandfather stands in the place of the father when the father is absent. al-Sirajiyya, bab al-jadd.',
        'input' => $hanafi + ['heirs' => ['paternal_grandfather' => true]],
        'expect' => ['shares' => ['paternal_grandfather' => '1']],
    ],
    [
        'id' => 'single_maternal_grandmother',
        'title' => 'Maternal grandmother alone',
        'group' => 'single_heir',
        'source' => 'One sixth for the true grandmother (hadith of al-Mughira b. Shuba, Abu Dawud), then radd.',
        'input' => $hanafi + ['heirs' => ['maternal_grandmother' => true]],
        'expect' => ['shares' => ['maternal_grandmother' => '1'], 'radd' => true],
    ],
    [
        'id' => 'single_sons_son',
        'title' => "Son's son alone",
        'group' => 'single_heir',
        'source' => "The agnatic grandson inherits as asaba when no son survives. al-Sirajiyya, bab al-asabat.",
        'input' => $hanafi + ['heirs' => ['sons_sons' => 1]],
        'expect' => ['shares' => ['sons_son' => '1']],
    ],
    [
        'id' => 'single_sons_daughter',
        'title' => "One son's daughter alone",
        'group' => 'single_heir',
        'source' => "The son's daughter takes the daughter's one half when no son and no daughter survives, then radd.",
        'input' => $hanafi + ['heirs' => ['sons_daughters' => 1]],
        'expect' => ['shares' => ['sons_daughter' => '1'], 'radd' => true],
    ],
    [
        'id' => 'single_full_brother',
        'title' => 'Full brother alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:176; with no descendant and no father the full brother takes the whole estate as asaba.",
        'input' => $hanafi + ['heirs' => ['full_brothers' => 1]],
        'expect' => ['shares' => ['full_brother' => '1']],
    ],
    [
        'id' => 'single_full_sister',
        'title' => 'One full sister alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:176 (one half for a single sister), then radd.",
        'input' => $hanafi + ['heirs' => ['full_sisters' => 1]],
        'expect' => ['shares' => ['full_sister' => '1'], 'radd' => true],
    ],
    [
        'id' => 'single_consanguine_sister',
        'title' => 'One consanguine sister alone',
        'group' => 'single_heir',
        'source' => "The consanguine sister takes the full sister's share in her absence. al-Sirajiyya, bab al-furud.",
        'input' => $hanafi + ['heirs' => ['consanguine_sisters' => 1]],
        'expect' => ['shares' => ['consanguine_sister' => '1'], 'radd' => true],
    ],
    [
        'id' => 'single_uterine_sibling',
        'title' => 'One uterine sibling alone',
        'group' => 'single_heir',
        'source' => "Qur'an 4:12 (one sixth for a single uterine sibling), then radd.",
        'input' => $hanafi + ['heirs' => ['uterine_siblings' => 1]],
        'expect' => ['shares' => ['uterine_sibling' => '1'], 'radd' => true],
    ],
    [
        'id' => 'two_uterine_siblings_alone',
        'title' => 'Two uterine siblings alone share equally',
        'group' => 'single_heir',
        'source' => "Qur'an 4:12 (two or more uterine siblings share one third), male and female alike, then radd.",
        'input' => $hanafi + ['heirs' => ['uterine_siblings' => 2]],
        'expect' => [
            'shares' => ['uterine_sibling' => '1'],
            'per_head' => ['uterine_sibling' => '1/2'],
            'radd' => true,
        ],
    ],
    [
        'id' => 'single_paternal_uncle',
        'title' => 'Full paternal uncle alone',
        'group' => 'single_heir',
        'source' => 'Fourth class of asaba: the grandfather\'s descendants. al-Sirajiyya, bab al-asabat.',
        'input' => $hanafi + ['heirs' => ['paternal_uncles' => 1]],
        'expect' => ['shares' => ['full_paternal_uncle' => '1']],
    ],
    [
        'id' => 'single_full_brother_son',
        'title' => "Full brother's son alone",
        'group' => 'single_heir',
        'source' => "Third class of asaba: the father's descendants. al-Sirajiyya, bab al-asabat.",
        'input' => $hanafi + ['heirs' => ['full_brother_sons' => 1]],
        'expect' => ['shares' => ['full_brother_son' => '1']],
    ],
];
