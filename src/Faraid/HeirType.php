<?php

declare(strict_types=1);

namespace Faraid;

/**
 * Every heir class the v1 engine knows about.
 *
 * Deliberately absent, and deferred to a later phase: haml (unborn child),
 * mafqud (missing person), khuntha (indeterminate gender) and dhawu al-arham
 * (distant kindred). Each needs its own scholarly handling.
 */
enum HeirType: string
{
    case Husband = 'husband';
    case Wife = 'wife';

    case Father = 'father';
    case Mother = 'mother';
    case PaternalGrandfather = 'paternal_grandfather';
    case MaternalGrandmother = 'maternal_grandmother';
    case PaternalGrandmother = 'paternal_grandmother';

    case Son = 'son';
    case Daughter = 'daughter';
    case SonsSon = 'sons_son';
    case SonsDaughter = 'sons_daughter';

    case FullBrother = 'full_brother';
    case FullSister = 'full_sister';
    case ConsanguineBrother = 'consanguine_brother';
    case ConsanguineSister = 'consanguine_sister';
    case UterineSibling = 'uterine_sibling';

    case FullBrotherSon = 'full_brother_son';
    case ConsanguineBrotherSon = 'consanguine_brother_son';
    case FullPaternalUncle = 'full_paternal_uncle';
    case ConsanguinePaternalUncle = 'consanguine_paternal_uncle';
    case FullPaternalUncleSon = 'full_paternal_uncle_son';
    case ConsanguinePaternalUncleSon = 'consanguine_paternal_uncle_son';

    /** Grandchildren inheriting by representation under MFLO 1961 s.4. */
    case PredeceasedChildsSon = 'predeceased_childs_son';
    case PredeceasedChildsDaughter = 'predeceased_childs_daughter';

    public function isMale(): bool
    {
        return match ($this) {
            self::Husband, self::Father, self::PaternalGrandfather, self::Son, self::SonsSon,
            self::FullBrother, self::ConsanguineBrother, self::FullBrotherSon,
            self::ConsanguineBrotherSon, self::FullPaternalUncle, self::ConsanguinePaternalUncle,
            self::FullPaternalUncleSon, self::ConsanguinePaternalUncleSon,
            self::PredeceasedChildsSon => true,
            // Uterine siblings are counted as a single mixed group: they are the
            // one class where sex does not change the share.
            default => false,
        };
    }

    public function isSpouse(): bool
    {
        return $this === self::Husband || $this === self::Wife;
    }

    /** Plural key used in the input array, e.g. "sons" for HeirType::Son. */
    public function inputKey(): string
    {
        return match ($this) {
            self::Husband => 'husband',
            self::Wife => 'wives',
            self::Father => 'father',
            self::Mother => 'mother',
            self::PaternalGrandfather => 'paternal_grandfather',
            self::MaternalGrandmother => 'maternal_grandmother',
            self::PaternalGrandmother => 'paternal_grandmother',
            self::Son => 'sons',
            self::Daughter => 'daughters',
            self::SonsSon => 'sons_sons',
            self::SonsDaughter => 'sons_daughters',
            self::FullBrother => 'full_brothers',
            self::FullSister => 'full_sisters',
            self::ConsanguineBrother => 'consanguine_brothers',
            self::ConsanguineSister => 'consanguine_sisters',
            self::UterineSibling => 'uterine_siblings',
            self::FullBrotherSon => 'full_brother_sons',
            self::ConsanguineBrotherSon => 'consanguine_brother_sons',
            self::FullPaternalUncle => 'paternal_uncles',
            self::ConsanguinePaternalUncle => 'consanguine_paternal_uncles',
            self::FullPaternalUncleSon => 'paternal_uncle_sons',
            self::ConsanguinePaternalUncleSon => 'consanguine_paternal_uncle_sons',
            self::PredeceasedChildsSon => 'predeceased_childs_sons',
            self::PredeceasedChildsDaughter => 'predeceased_childs_daughters',
        };
    }
}
