<?php

declare(strict_types=1);

/**
 * Section 4 of the Muslim Family Laws Ordinance 1961 (Pakistan): the children
 * of a predeceased son or daughter take, by representation, the share their
 * parent would have taken.
 *
 * This is statute, not fiqh. It is off by default, and every result that uses
 * it carries a warning to confirm the current legal position with a Pakistani
 * lawyer.
 *
 * Two constructions exist and they give different answers. The words of the
 * section give the share to the children of the predeceased child. The courts
 * read it as giving that child a notional share which then passes to ALL of
 * their heirs — Kamal Khan v Mst. Zainab, PLD 1983 Lahore 546, endorsed by the
 * Supreme Court in Mst. Zainab v Kamal Khan, PLD 1990 SC 1051. The engine
 * defaults to the courts' construction.
 *
 * The section was also held repugnant to the injunctions of Islam by the
 * Federal Shariat Court in Allah Rakha v Federation of Pakistan, PLD 2000 SC 1;
 * an appeal to the Shariat Appellate Bench is pending, which suspends that
 * declaration. See docs/SOURCES-CONSULTED.md.
 */

$male = ['madhhab' => 'hanafi', 'deceased_gender' => 'male'];

return [
    [
        'id' => 'mflo_off_grandchildren_take_nothing',
        'title' => 'Classical rules: a living son excludes the predeceased son\'s children',
        'group' => 'mflo_1961',
        'source' => 'Classical Hanafi: the nearer agnatic descendant excludes the further. al-Sirajiyya, bab al-hajb.',
        'input' => $male + [
            'apply_mflo_1961' => false,
            'heirs' => ['sons' => 2],
            'predeceased_children' => [['gender' => 'male', 'sons' => 2, 'daughters' => 0]],
        ],
        'expect' => [
            'shares' => ['son' => '1'],
            'mflo' => false,
            'warnings' => ['predeceased_children_ignored_without_mflo'],
        ],
    ],
    [
        'id' => 'mflo_on_grandsons_take_their_fathers_share',
        'title' => "MFLO 1961 s.4: the predeceased son's children take his third",
        'group' => 'mflo_1961',
        'source' => 'Muslim Family Laws Ordinance 1961, section 4 (Pakistan): per stirpes representation for the children of a predeceased son or daughter.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['sons' => 2],
            'predeceased_children' => [['gender' => 'male', 'sons' => 2, 'daughters' => 0]],
        ],
        'expect' => [
            'shares' => ['son' => '2/3', 'predeceased_childs_son@predeceased_son_1' => '1/3'],
            'mflo' => true,
            'warnings' => ['mflo_1961_applied', 'mflo_1961_confirm_with_lawyer'],
        ],
    ],
    [
        'id' => 'mflo_on_predeceased_daughters_children',
        'title' => "MFLO 1961 s.4: a predeceased daughter's children take her share two to one",
        'group' => 'mflo_1961',
        'source' => 'Muslim Family Laws Ordinance 1961, section 4, which covers a predeceased daughter as well as a predeceased son.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['sons' => 1],
            'predeceased_children' => [['gender' => 'female', 'sons' => 1, 'daughters' => 1]],
        ],
        'expect' => [
            'shares' => [
                'son' => '2/3',
                'predeceased_childs_son@predeceased_daughter_1' => '2/9',
                'predeceased_childs_daughter@predeceased_daughter_1' => '1/9',
            ],
            'denominator' => 9,
            'mflo' => true,
        ],
    ],
    [
        'id' => 'mflo_on_with_a_wife',
        'title' => 'MFLO 1961 s.4 alongside a spouse share',
        'group' => 'mflo_1961',
        'source' => "Muslim Family Laws Ordinance 1961, section 4; the wife's eighth is taken first and the representation applies to the residue.",
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['wives' => 1, 'sons' => 1],
            'predeceased_children' => [['gender' => 'male', 'sons' => 2, 'daughters' => 0]],
        ],
        'expect' => [
            'shares' => [
                'wife' => '1/8',
                'son' => '7/16',
                'predeceased_childs_son@predeceased_son_1' => '7/16',
            ],
            'denominator' => 16,
            'mflo' => true,
        ],
    ],
    [
        'id' => 'mflo_settled_construction_reaches_the_predeceased_sons_widow',
        'title' => "The courts' construction: the predeceased son's widow shares his notional portion",
        'group' => 'mflo_1961',
        'source' => 'Kamal Khan v Mst. Zainab, PLD 1983 Lahore 546, endorsed in Mst. Zainab v Kamal Khan, PLD 1990 SC 1051: the predeceased child takes a notional share which is then distributed among that child\'s own heirs, as if the child had died immediately after the propositus. Described in "Inheritance Rights of Orphaned Grandchildren: A Straightforward Provision of Law?", LUMS SAHSOL.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['sons' => 2],
            'predeceased_children' => [
                ['gender' => 'male', 'sons' => 2, 'heirs' => ['wives' => 1]],
            ],
        ],
        'expect' => [
            'shares' => [
                'son' => '2/3',
                'wife@predeceased_son_1' => '1/24',
                'predeceased_childs_son@predeceased_son_1' => '7/24',
            ],
            'denominator' => 24,
            'mflo' => true,
            'warnings' => ['mflo_settled_construction'],
        ],
    ],
    [
        'id' => 'mflo_textual_construction_gives_the_children_everything',
        'title' => 'The words of the section instead give the whole notional share to the children',
        'group' => 'mflo_1961',
        'source' => 'The text of section 4 gives the share to "the children of such son or daughter". This is the construction the courts applied in the first two decades after the Ordinance, before Kamal Khan; offered as an option so the difference can be seen.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'mflo_construction' => 'textual',
            'heirs' => ['sons' => 2],
            'predeceased_children' => [
                ['gender' => 'male', 'sons' => 2, 'heirs' => ['wives' => 1]],
            ],
        ],
        'expect' => [
            'shares' => [
                'son' => '2/3',
                'predeceased_childs_son@predeceased_son_1' => '1/3',
            ],
            'mflo' => true,
            'not_warnings' => ['mflo_settled_construction'],
        ],
    ],
    [
        'id' => 'mflo_a_widow_can_inherit_twice_under_the_settled_construction',
        'title' => 'A widow can take from her husband and again from her predeceased son',
        'group' => 'mflo_1961',
        'source' => 'A consequence of the notional-share construction: the deceased\'s widow is also the mother of the predeceased son, so she inherits from her husband as a wife and from her son\'s notional share as his mother. Recorded because it surprises people, not because it is disputed.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['wives' => 1, 'sons' => 1],
            'predeceased_children' => [
                ['gender' => 'male', 'sons' => 1, 'heirs' => ['mother' => 1]],
            ],
        ],
        'expect' => [
            'shares' => [
                'wife' => '1/8',
                'son' => '7/16',
                'mother@predeceased_son_1' => '7/96',
                'predeceased_childs_son@predeceased_son_1' => '35/96',
            ],
            'denominator' => 96,
            'mflo' => true,
        ],
    ],
    [
        'id' => 'mflo_predeceased_child_without_issue_rejected',
        'title' => 'A predeceased child with no surviving children cannot be represented',
        'group' => 'mflo_1961',
        'source' => 'Section 4 gives the share to the children of the predeceased child; with no such children there is nobody to represent.',
        'input' => $male + [
            'apply_mflo_1961' => true,
            'heirs' => ['sons' => 1],
            'predeceased_children' => [['gender' => 'male', 'sons' => 0, 'daughters' => 0]],
        ],
        'expect_error' => 'cannot inherit by representation',
    ],
];
