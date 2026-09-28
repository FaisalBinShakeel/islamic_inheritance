<?php

declare(strict_types=1);

namespace Faraid;

use Faraid\Exception\CalculationError;

/**
 * The Faraid calculation engine.
 *
 * A pure class: it takes an array in, returns a Result out, and touches no
 * database, no HTTP, no session and no clock. That is what makes it testable,
 * and testability is the only reason anyone should trust its output.
 *
 * The rules implemented here are classical Sunni Faraid, with the four schools
 * differing only where Madhhab\MadhhabRules says they differ. They have NOT
 * yet been signed off by a qualified scholar. Until that review is done and
 * recorded in docs/REVIEW-CHECKLIST.md, this is a developer's reading of the
 * classical rule tables and must not be presented to the public as a fatwa.
 */
final class Calculator
{
    /**
     * Residuary classes (asaba bi nafsihi) in strict order of nearness.
     * A nearer class excludes every further class entirely.
     *
     * @var list<array{0:HeirType,1:?HeirType}> [residuary, female relative who
     *      becomes residuary alongside him at 2:1]
     */
    private const RESIDUARY_ORDER = [
        [HeirType::Son, HeirType::Daughter],
        [HeirType::SonsSon, HeirType::SonsDaughter],
        [HeirType::Father, null],
        [HeirType::PaternalGrandfather, null],
        [HeirType::FullBrother, HeirType::FullSister],
        [HeirType::ConsanguineBrother, HeirType::ConsanguineSister],
        [HeirType::FullBrotherSon, null],
        [HeirType::ConsanguineBrotherSon, null],
        [HeirType::FullPaternalUncle, null],
        [HeirType::ConsanguinePaternalUncle, null],
        [HeirType::FullPaternalUncleSon, null],
        [HeirType::ConsanguinePaternalUncleSon, null],
    ];

    public function calculate(array $raw): Result
    {
        return $this->run(Input::fromArray($raw));
    }

    public function run(Input $input): Result
    {
        $state = new CalculationState($input);

        $this->applyRepresentation($state);
        $this->applyExclusions($state);

        if (!$this->applyUmariyyatan($state) && !$this->applyAkdariyya($state)) {
            $this->assignQuranicShares($state);
            $this->distributeResidue($state);
        }

        $this->applyRepresentationPayout($state);

        $result = $state->toResult();
        $result->assertBalanced();

        return $result;
    }

    // ---------------------------------------------------------------------
    // MFLO 1961 s.4 — representation of a predeceased child
    // ---------------------------------------------------------------------

    /**
     * Section 4 of the Muslim Family Laws Ordinance 1961 is statute, not
     * fiqh: in Pakistan the children of a predeceased son or daughter take
     * the share their parent would have taken. Classical rules would exclude
     * them where a living son survives.
     *
     * Implemented by treating each predeceased child as if alive, running the
     * ordinary calculation, then paying that child's individual portion down
     * to their own children at 2:1. See applyRepresentationPayout().
     */
    private function applyRepresentation(CalculationState $state): void
    {
        if ($state->input->predeceasedChildren === []) {
            return;
        }

        if (!$state->input->applyMflo1961) {
            // Without the statutory override these grandchildren are either
            // ordinary agnatic grandchildren (through a son, which the caller
            // should have entered as sons_sons / sons_daughters) or distant
            // kindred (through a daughter), which v1 does not handle.
            return;
        }

        $state->mfloApplied = true;
        $state->addWarning('mflo_1961_applied');
        $state->addWarning('mflo_1961_confirm_with_lawyer');

        foreach ($state->input->predeceasedChildren as $index => $child) {
            $label = sprintf('predeceased_%s_%d', $child['gender'] === 'male' ? 'son' : 'daughter', $index + 1);
            $state->representation[] = $child + ['label' => $label];
            if ($child['gender'] === 'male') {
                $state->counts['sons']++;
                $state->virtualSons++;
            } else {
                $state->counts['daughters']++;
                $state->virtualDaughters++;
            }
        }
    }

    /** Move each represented child's individual portion down to their children. */
    private function applyRepresentationPayout(CalculationState $state): void
    {
        if (!$state->mfloApplied) {
            return;
        }

        foreach ([['sons', HeirType::Son, 'male'], ['daughters', HeirType::Daughter, 'female']] as [$key, $type, $gender]) {
            $virtual = $gender === 'male' ? $state->virtualSons : $state->virtualDaughters;
            if ($virtual === 0) {
                continue;
            }

            $share = $state->shareFor($type);
            if ($share === null) {
                // The represented child's class took nothing at all, so there
                // is nothing to pass down.
                continue;
            }

            $perHead = $share->perHead();
            $remainingLiving = $share->count - $virtual;

            if ($remainingLiving > 0) {
                $state->setShare($share->withShare($perHead->multiply(Fraction::of($remainingLiving))));
                $state->replaceCount($type, $remainingLiving);
            } else {
                $state->removeShare($type);
            }

            foreach ($state->representation as $child) {
                if ($child['gender'] !== $gender) {
                    continue;
                }
                $this->payRepresentedChild($state, $perHead, $child);
            }
        }
    }

    /**
     * Pay a represented child's notional share onward.
     *
     * Two constructions of section 4 exist, and they give different answers.
     *
     * The words of the statute give the share to "the children of such son or
     * daughter". The Lahore High Court in Kamal Khan v Mst. Zainab read it
     * differently — the predeceased child is deemed to come back to life to
     * take the share and then die again, so the notional share passes to ALL
     * of that child's heirs, their widow and mother included, not only their
     * children. The Supreme Court endorsed that reading in Mst. Zainab v Kamal
     * Khan, PLD 1990 SC 1051, and it is the construction the courts apply.
     *
     * The engine defaults to the settled construction and offers the textual
     * one as an option, because a Pakistani user asking what the law gives
     * them needs the answer the courts would give.
     *
     * @param array{gender:string,sons:int,daughters:int,own_heirs:array<string,int>,label:string} $child
     */
    private function payRepresentedChild(CalculationState $state, Fraction $portion, array $child): void
    {
        if ($state->input->mfloConstruction === 'settled') {
            $this->paySettledConstruction($state, $portion, $child);

            return;
        }

        $units = $child['sons'] * 2 + $child['daughters'];
        if ($units === 0) {
            // The textual construction has nobody to pay: the statute names
            // the children, and this child left none.
            $state->undistributed = $state->undistributed->add($portion);
            $state->addWarning('mflo_textual_construction_no_children');

            return;
        }

        $unitShare = $portion->divide(Fraction::of($units));

        if ($child['sons'] > 0) {
            $state->addShare(new Share(
                HeirType::PredeceasedChildsSon,
                $child['sons'],
                $unitShare->multiply(Fraction::of($child['sons'] * 2)),
                'representation',
                'mflo_representation_grandson',
                $child['label'],
            ));
        }
        if ($child['daughters'] > 0) {
            $state->addShare(new Share(
                HeirType::PredeceasedChildsDaughter,
                $child['daughters'],
                $unitShare->multiply(Fraction::of($child['daughters'])),
                'representation',
                'mflo_representation_granddaughter',
                $child['label'],
            ));
        }
    }

    /**
     * The notional share is itself an estate, distributed among the
     * predeceased child's heirs by the ordinary rules — so the engine simply
     * runs itself again on that heir set and scales the result.
     *
     * @param array{gender:string,sons:int,daughters:int,own_heirs:array<string,int>,label:string} $child
     */
    private function paySettledConstruction(CalculationState $state, Fraction $portion, array $child): void
    {
        $nested = $this->run(Input::fromArray([
            'madhhab' => $state->input->madhhab->key(),
            'deceased_gender' => $child['gender'],
            'heirs' => $child['own_heirs'],
        ]));

        foreach ($nested->shares as $share) {
            // From the deceased's side these are grandchildren, so name them
            // that way. Everyone else — the child's widow, their mother — keeps
            // their own relationship, which the "through whom" label makes
            // readable.
            $heir = match ($share->heir) {
                HeirType::Son => HeirType::PredeceasedChildsSon,
                HeirType::Daughter => HeirType::PredeceasedChildsDaughter,
                default => $share->heir,
            };

            $reasonKey = match ($share->heir) {
                HeirType::Son => 'mflo_representation_grandson',
                HeirType::Daughter => 'mflo_representation_granddaughter',
                default => $share->reasonKey,
            };

            $state->addShare(new Share(
                $heir,
                $share->count,
                $portion->multiply($share->share),
                'representation',
                $reasonKey,
                $child['label'],
            ));
        }

        if ($nested->undistributed->isPositive()) {
            $state->undistributed = $state->undistributed->add($portion->multiply($nested->undistributed));
        }

        foreach ($nested->warnings as $warning) {
            if (str_starts_with($warning, 'madhhab_')) {
                continue;
            }
            $state->addWarning($warning);
        }

        $state->addWarning('mflo_settled_construction');

        // Only children were entered, so the answer happens to match the
        // textual reading — but a widow or a surviving mother of that child
        // would change it, and the user should be told they were not asked.
        $onlyChildren = array_diff(array_keys($child['own_heirs']), ['sons', 'daughters']) === [];
        if ($onlyChildren) {
            $state->addWarning('mflo_other_heirs_of_predeceased_child_not_entered');
        }
    }

    // ---------------------------------------------------------------------
    // Hajb — exclusion
    // ---------------------------------------------------------------------

    private function applyExclusions(CalculationState $state): void
    {
        $c = $state->counts;
        $rules = $state->input->madhhab;

        // Hajb by presence, not by inheritance: an heir who is himself
        // excluded still blocks those further out. The one thing that does not
        // block is disqualification, which Input already stripped out.
        $hasSon = $c['sons'] > 0;
        $hasFather = $c['father'] > 0;
        $hasMother = $c['mother'] > 0;

        // 1. Ascendants
        if ($c['paternal_grandfather'] > 0 && $hasFather) {
            $state->exclude(HeirType::PaternalGrandfather, 'excluded_by_father', HeirType::Father);
        }
        $hasGrandfather = $state->live(HeirType::PaternalGrandfather) > 0;

        if ($c['maternal_grandmother'] > 0 && $hasMother) {
            $state->exclude(HeirType::MaternalGrandmother, 'excluded_by_mother', HeirType::Mother);
        }
        if ($c['paternal_grandmother'] > 0) {
            if ($hasMother) {
                $state->exclude(HeirType::PaternalGrandmother, 'excluded_by_mother', HeirType::Mother);
            } elseif ($hasFather) {
                $state->exclude(HeirType::PaternalGrandmother, 'excluded_by_father', HeirType::Father);
            }
        }

        // 2. Agnatic grandchildren
        if ($c['sons_sons'] > 0 && $hasSon) {
            $state->exclude(HeirType::SonsSon, 'excluded_by_son', HeirType::Son);
        }
        $hasSonsSon = $state->live(HeirType::SonsSon) > 0;

        if ($c['sons_daughters'] > 0) {
            if ($hasSon) {
                $state->exclude(HeirType::SonsDaughter, 'excluded_by_son', HeirType::Son);
            } elseif ($c['daughters'] >= 2 && !$hasSonsSon) {
                // Two daughters already take the whole 2/3 reserved for female
                // descendants. A son's son at her level would rescue her by
                // making her residuary; without him she takes nothing.
                $state->exclude(HeirType::SonsDaughter, 'excluded_by_two_daughters', HeirType::Daughter);
            }
        }
        $hasSonsDaughter = $state->live(HeirType::SonsDaughter) > 0;

        $state->hasMaleDescendant = $hasSon || $hasSonsSon;
        // For the spouse's and the mother's shares, any child or agnatic
        // grandchild counts, whether or not they end up inheriting.
        $state->hasDescendant = $hasSon
            || $c['daughters'] > 0
            || $c['sons_sons'] > 0
            || $c['sons_daughters'] > 0;
        $state->hasFemaleDescendantInheriting = $c['daughters'] > 0 || $hasSonsDaughter;

        // 3. Siblings
        $siblingBlockers = [];
        if ($state->hasMaleDescendant) {
            $siblingBlockers[] = ['reason' => 'excluded_by_male_descendant', 'by' => $hasSon ? HeirType::Son : HeirType::SonsSon];
        }
        if ($hasFather) {
            $siblingBlockers[] = ['reason' => 'excluded_by_father', 'by' => HeirType::Father];
        }

        $uterineBlockers = $siblingBlockers;
        if ($state->hasDescendant && !$state->hasMaleDescendant) {
            // Uterine siblings are excluded by a daughter or son's daughter
            // too, which the agnatic siblings are not.
            array_unshift($uterineBlockers, ['reason' => 'excluded_by_descendant', 'by' => HeirType::Daughter]);
        }
        if ($hasGrandfather) {
            $uterineBlockers[] = ['reason' => 'excluded_by_grandfather', 'by' => HeirType::PaternalGrandfather];
            if ($rules->grandfatherExcludesSiblings()) {
                $siblingBlockers[] = ['reason' => 'excluded_by_grandfather', 'by' => HeirType::PaternalGrandfather];
            }
        }

        foreach ([HeirType::FullBrother, HeirType::FullSister, HeirType::ConsanguineBrother, HeirType::ConsanguineSister] as $type) {
            if ($state->live($type) > 0 && $siblingBlockers !== []) {
                $state->exclude($type, $siblingBlockers[0]['reason'], $siblingBlockers[0]['by']);
            }
        }
        if ($state->live(HeirType::UterineSibling) > 0 && $uterineBlockers !== []) {
            $state->exclude(HeirType::UterineSibling, $uterineBlockers[0]['reason'], $uterineBlockers[0]['by']);
        }

        $liveFullBrothers = $state->live(HeirType::FullBrother);
        $liveFullSisters = $state->live(HeirType::FullSister);

        // Full siblings shut out consanguine siblings.
        if ($liveFullBrothers > 0) {
            foreach ([HeirType::ConsanguineBrother, HeirType::ConsanguineSister] as $type) {
                if ($state->live($type) > 0) {
                    $state->exclude($type, 'excluded_by_full_brother', HeirType::FullBrother);
                }
            }
        }

        $fullSistersAreMaAlGhayr = $liveFullSisters > 0
            && $liveFullBrothers === 0
            && $state->hasFemaleDescendantInheriting;

        if ($state->live(HeirType::ConsanguineSister) > 0) {
            if ($fullSistersAreMaAlGhayr) {
                // A full sister made residuary by a daughter stands in the
                // place of a full brother, and excludes consanguine sisters.
                $state->exclude(HeirType::ConsanguineSister, 'excluded_by_full_sister_as_residuary', HeirType::FullSister);
            } elseif ($liveFullSisters >= 2 && $state->live(HeirType::ConsanguineBrother) === 0) {
                // Two full sisters have taken the whole 2/3.
                $state->exclude(HeirType::ConsanguineSister, 'excluded_by_two_full_sisters', HeirType::FullSister);
            }
        }
        if ($state->live(HeirType::ConsanguineBrother) > 0 && $fullSistersAreMaAlGhayr) {
            $state->exclude(HeirType::ConsanguineBrother, 'excluded_by_full_sister_as_residuary', HeirType::FullSister);
        }

        $state->fullSistersAreResiduaryWithDaughters = $fullSistersAreMaAlGhayr;
        $state->consanguineSistersAreResiduaryWithDaughters = $state->live(HeirType::ConsanguineSister) > 0
            && $state->live(HeirType::ConsanguineBrother) === 0
            && $liveFullSisters === 0
            && $state->hasFemaleDescendantInheriting;

        // Outside the Hanafi school a sister who survives beside the true
        // grandfather does not take her Quranic half or two thirds: under
        // Zayd's doctrine she joins him as a residuary in the muqasama. The
        // one exception is al-Akdariyya, which is settled before this point.
        $state->siblingsCompeteWithGrandfather = !$rules->grandfatherExcludesSiblings()
            && $hasGrandfather
            && (
                $state->live(HeirType::FullBrother)
                + $liveFullSisters
                + $state->live(HeirType::ConsanguineBrother)
                + $state->live(HeirType::ConsanguineSister)
            ) > 0;
    }

    // ---------------------------------------------------------------------
    // Named cases that replace the ordinary calculation
    // ---------------------------------------------------------------------

    /**
     * Umariyyatan (al-Gharrawayn): spouse, mother and father, and nobody else.
     * The mother takes a third of what is left after the spouse, not a third
     * of the whole estate.
     */
    private function applyUmariyyatan(CalculationState $state): bool
    {
        if (!$state->survivorsAreExactly([HeirType::Mother, HeirType::Father], allowSpouse: true)) {
            return false;
        }
        if ($state->live(HeirType::Mother) === 0 || $state->live(HeirType::Father) === 0) {
            return false;
        }

        $spouseType = $state->live(HeirType::Husband) > 0 ? HeirType::Husband : ($state->live(HeirType::Wife) > 0 ? HeirType::Wife : null);
        if ($spouseType === null) {
            return false;
        }

        $spouseShare = $spouseType === HeirType::Husband ? Fraction::of(1, 2) : Fraction::of(1, 4);
        $remainder = Fraction::one()->subtract($spouseShare);
        $motherShare = $remainder->divide(Fraction::of(3));
        $fatherShare = $remainder->subtract($motherShare);

        $state->specialCase = 'umariyyatan';
        $state->addWarning('umariyyatan_applied');

        $state->addShare(new Share(
            $spouseType,
            $state->live($spouseType),
            $spouseShare,
            'quranic',
            $spouseType === HeirType::Husband ? 'husband_no_descendant' : 'wife_no_descendant',
        ));
        $state->addShare(new Share(HeirType::Mother, 1, $motherShare, 'quranic', 'mother_umariyyatan'));
        $state->addShare(new Share(HeirType::Father, 1, $fatherShare, 'residuary', 'father_residuary'));

        return true;
    }

    /**
     * Al-Akdariyya: husband, mother, true grandfather and one sister, and
     * nobody else. Cannot arise under Hanafi rules, where the grandfather has
     * already excluded the sister.
     *
     * The shares first go to awl over nine; the grandfather's sixth and the
     * sister's half are then pooled and redivided two to one, giving the
     * classic denominator of twenty-seven.
     */
    private function applyAkdariyya(CalculationState $state): bool
    {
        if (!$state->input->madhhab->appliesAkdariyya()) {
            return false;
        }
        if ($state->live(HeirType::Husband) !== 1
            || $state->live(HeirType::Mother) !== 1
            || $state->live(HeirType::PaternalGrandfather) !== 1) {
            return false;
        }

        $sisterType = null;
        if ($state->live(HeirType::FullSister) === 1) {
            $sisterType = HeirType::FullSister;
        } elseif ($state->live(HeirType::ConsanguineSister) === 1) {
            $sisterType = HeirType::ConsanguineSister;
        }
        if ($sisterType === null) {
            return false;
        }

        if (!$state->survivorsAreExactly([HeirType::Mother, HeirType::PaternalGrandfather, $sisterType], allowSpouse: true)) {
            return false;
        }

        $state->specialCase = 'akdariyya';
        $state->awlApplied = true;
        $state->addWarning('akdariyya_applied');

        $state->addShare(new Share(HeirType::Husband, 1, Fraction::of(9, 27), 'quranic', 'husband_no_descendant'));
        $state->addShare(new Share(HeirType::Mother, 1, Fraction::of(6, 27), 'quranic', 'mother_one_third'));
        $state->addShare(new Share(HeirType::PaternalGrandfather, 1, Fraction::of(8, 27), 'quranic_and_residuary', 'grandfather_akdariyya'));
        $state->addShare(new Share($sisterType, 1, Fraction::of(4, 27), 'quranic_and_residuary', 'sister_akdariyya'));

        return true;
    }

    // ---------------------------------------------------------------------
    // Fixed shares
    // ---------------------------------------------------------------------

    private function assignQuranicShares(CalculationState $state): void
    {
        $this->assignSpouse($state);
        $this->assignParents($state);
        $this->assignGrandparents($state);
        $this->assignDescendants($state);
        $this->assignSiblings($state);
    }

    private function assignSpouse(CalculationState $state): void
    {
        if ($state->live(HeirType::Husband) > 0) {
            $state->addShare(new Share(
                HeirType::Husband,
                1,
                $state->hasDescendant ? Fraction::of(1, 4) : Fraction::of(1, 2),
                'quranic',
                $state->hasDescendant ? 'husband_with_descendant' : 'husband_no_descendant',
            ));
        }

        $wives = $state->live(HeirType::Wife);
        if ($wives > 0) {
            // All wives share the single spousal portion between them.
            $state->addShare(new Share(
                HeirType::Wife,
                $wives,
                $state->hasDescendant ? Fraction::of(1, 8) : Fraction::of(1, 4),
                'quranic',
                $state->hasDescendant ? 'wife_with_descendant' : 'wife_no_descendant',
            ));
        }
    }

    private function assignParents(CalculationState $state): void
    {
        if ($state->live(HeirType::Mother) > 0) {
            // Siblings reduce the mother's share to a sixth even when they are
            // themselves excluded by the father — hajb nuqsan by presence.
            $siblings = $state->siblingCountForMothersShare();
            if ($state->hasDescendant) {
                $state->addShare(new Share(HeirType::Mother, 1, Fraction::of(1, 6), 'quranic', 'mother_with_descendant'));
            } elseif ($siblings >= 2) {
                $state->addShare(new Share(HeirType::Mother, 1, Fraction::of(1, 6), 'quranic', 'mother_with_two_or_more_siblings'));
            } else {
                $state->addShare(new Share(HeirType::Mother, 1, Fraction::of(1, 3), 'quranic', 'mother_one_third'));
            }
        }

        foreach ([[HeirType::Father, 'father'], [HeirType::PaternalGrandfather, 'grandfather']] as [$type, $label]) {
            if ($state->live($type) === 0) {
                continue;
            }
            if ($state->hasMaleDescendant) {
                $state->addShare(new Share($type, 1, Fraction::of(1, 6), 'quranic', $label . '_with_male_descendant'));
            } elseif ($state->hasDescendant) {
                // A sixth as a sharer, and whatever is left afterwards as
                // residuary. The residue arrives in distributeResidue().
                $state->addShare(new Share($type, 1, Fraction::of(1, 6), 'quranic', $label . '_with_female_descendant'));
            }
            // With no descendant at all he is purely residuary.
        }
    }

    private function assignGrandparents(CalculationState $state): void
    {
        $maternal = $state->live(HeirType::MaternalGrandmother);
        $paternal = $state->live(HeirType::PaternalGrandmother);
        $total = $maternal + $paternal;
        if ($total === 0) {
            return;
        }

        // The true grandmothers share a single sixth between them.
        $sixth = Fraction::of(1, 6);
        if ($maternal > 0) {
            $state->addShare(new Share(
                HeirType::MaternalGrandmother,
                $maternal,
                $sixth->multiply(Fraction::of($maternal, $total)),
                'quranic',
                'grandmother_one_sixth',
            ));
        }
        if ($paternal > 0) {
            $state->addShare(new Share(
                HeirType::PaternalGrandmother,
                $paternal,
                $sixth->multiply(Fraction::of($paternal, $total)),
                'quranic',
                'grandmother_one_sixth',
            ));
        }
    }

    private function assignDescendants(CalculationState $state): void
    {
        $daughters = $state->live(HeirType::Daughter);
        $sons = $state->live(HeirType::Son);
        $sonsDaughters = $state->live(HeirType::SonsDaughter);
        $sonsSons = $state->live(HeirType::SonsSon);

        if ($daughters > 0 && $sons === 0) {
            $state->addShare(new Share(
                HeirType::Daughter,
                $daughters,
                $daughters === 1 ? Fraction::of(1, 2) : Fraction::of(2, 3),
                'quranic',
                $daughters === 1 ? 'one_daughter_half' : 'daughters_two_thirds',
            ));
        }

        if ($sonsDaughters > 0 && $sonsSons === 0 && $sons === 0) {
            if ($daughters === 0) {
                $state->addShare(new Share(
                    HeirType::SonsDaughter,
                    $sonsDaughters,
                    $sonsDaughters === 1 ? Fraction::of(1, 2) : Fraction::of(2, 3),
                    'quranic',
                    $sonsDaughters === 1 ? 'one_sons_daughter_half' : 'sons_daughters_two_thirds',
                ));
            } elseif ($daughters === 1) {
                // The sixth that completes the daughters' two thirds.
                $state->addShare(new Share(
                    HeirType::SonsDaughter,
                    $sonsDaughters,
                    Fraction::of(1, 6),
                    'quranic',
                    'sons_daughter_completes_two_thirds',
                ));
            }
        }
    }

    private function assignSiblings(CalculationState $state): void
    {
        $fullSisters = $state->live(HeirType::FullSister);
        $fullBrothers = $state->live(HeirType::FullBrother);
        $consanguineSisters = $state->live(HeirType::ConsanguineSister);
        $consanguineBrothers = $state->live(HeirType::ConsanguineBrother);

        if ($state->siblingsCompeteWithGrandfather) {
            // Handled by applyZaydGrandfatherDoctrine(), which divides the
            // residue between the grandfather and the siblings together.
            $this->assignUterineSiblings($state);

            return;
        }

        if ($fullSisters > 0 && $fullBrothers === 0 && !$state->fullSistersAreResiduaryWithDaughters) {
            $state->addShare(new Share(
                HeirType::FullSister,
                $fullSisters,
                $fullSisters === 1 ? Fraction::of(1, 2) : Fraction::of(2, 3),
                'quranic',
                $fullSisters === 1 ? 'one_full_sister_half' : 'full_sisters_two_thirds',
            ));
        }

        if ($consanguineSisters > 0 && $consanguineBrothers === 0 && !$state->consanguineSistersAreResiduaryWithDaughters) {
            if ($fullSisters === 1 && !$state->fullSistersAreResiduaryWithDaughters) {
                $state->addShare(new Share(
                    HeirType::ConsanguineSister,
                    $consanguineSisters,
                    Fraction::of(1, 6),
                    'quranic',
                    'consanguine_sister_completes_two_thirds',
                ));
            } elseif ($fullSisters === 0) {
                $state->addShare(new Share(
                    HeirType::ConsanguineSister,
                    $consanguineSisters,
                    $consanguineSisters === 1 ? Fraction::of(1, 2) : Fraction::of(2, 3),
                    'quranic',
                    $consanguineSisters === 1 ? 'one_consanguine_sister_half' : 'consanguine_sisters_two_thirds',
                ));
            }
        }

        $this->assignUterineSiblings($state);
    }

    private function assignUterineSiblings(CalculationState $state): void
    {
        $uterine = $state->live(HeirType::UterineSibling);
        if ($uterine === 0) {
            return;
        }

        $state->addShare(new Share(
            HeirType::UterineSibling,
            $uterine,
            $uterine === 1 ? Fraction::of(1, 6) : Fraction::of(1, 3),
            'quranic',
            $uterine === 1 ? 'one_uterine_sibling_sixth' : 'uterine_siblings_third_shared_equally',
        ));
    }

    // ---------------------------------------------------------------------
    // Residue, awl and radd
    // ---------------------------------------------------------------------

    private function distributeResidue(CalculationState $state): void
    {
        $assigned = $state->totalAssigned();
        $residue = Fraction::one()->subtract($assigned);

        if ($residue->isNegative()) {
            $this->applyAwl($state, $assigned);
            $this->markResiduariesWithNothing($state, 'no_residue_after_awl');

            return;
        }

        if ($residue->isZero()) {
            $this->applyMushtaraka($state);
            $this->markResiduariesWithNothing($state, 'no_residue_remaining');

            return;
        }

        if (!$this->payResiduaries($state, $residue)) {
            $this->applyRadd($state, $residue);
        }
    }

    /**
     * Awl: the fixed shares between them claim more than the whole estate, so
     * the common denominator is raised to the sum of the numerators and every
     * heir is reduced in proportion. Dividing each share by the total does
     * exactly that, and keeps the arithmetic exact.
     */
    private function applyAwl(CalculationState $state, Fraction $assigned): void
    {
        foreach ($state->shares() as $share) {
            $state->setShare($share->withShare($share->share->divide($assigned)));
        }

        $state->awlApplied = true;
        $state->addWarning('awl_applied');
    }

    /**
     * Radd: the fixed shares leave a surplus and no residuary survives, so the
     * surplus returns to the fixed-share heirs in proportion — never to a
     * spouse.
     */
    private function applyRadd(CalculationState $state, Fraction $residue): void
    {
        if (!$state->input->madhhab->appliesRadd()) {
            $state->undistributed = $residue;
            $state->addWarning('surplus_to_bayt_al_mal_no_radd');

            return;
        }

        $eligible = array_values(array_filter(
            $state->shares(),
            static fn (Share $share) => !$share->heir->isSpouse(),
        ));

        $eligibleTotal = Fraction::sum(array_map(static fn (Share $s) => $s->share, $eligible));

        if ($eligibleTotal->isZero()) {
            // Nobody but a spouse survives. Classically the surplus goes to the
            // public treasury; the engine reports it rather than inventing an
            // heir for it.
            if ($state->input->madhhab->raddToSpouseWhenSoleHeir()) {
                foreach ($state->shares() as $share) {
                    $state->setShare(
                        $share->withShare($share->share->add($residue))
                            ->withBasis('quranic_and_radd', 'spouse_sole_heir_takes_all')
                    );
                }
                $state->raddApplied = true;
                $state->addWarning('radd_returned_to_spouse_as_sole_heir');

                return;
            }

            $state->undistributed = $residue;
            $state->addWarning('surplus_undistributed_spouse_excluded_from_radd');

            return;
        }

        $factor = $residue->divide($eligibleTotal);
        foreach ($eligible as $share) {
            $state->setShare(
                $share->withShare($share->share->add($share->share->multiply($factor)))
                    ->withBasis('quranic_and_radd')
            );
        }

        $state->raddApplied = true;
        $state->addWarning('radd_applied');
        if ($state->live(HeirType::Husband) > 0 || $state->live(HeirType::Wife) > 0) {
            $state->addWarning('radd_excludes_spouse');
        }
    }

    /**
     * Mushtaraka / Himariyya: husband, mother (or grandmother) taking a sixth,
     * two or more uterine siblings taking a third, and full brothers left with
     * nothing because the fixed shares have consumed the estate.
     */
    private function applyMushtaraka(CalculationState $state): void
    {
        if ($state->live(HeirType::Husband) !== 1) {
            return;
        }
        $uterine = $state->live(HeirType::UterineSibling);
        $fullBrothers = $state->live(HeirType::FullBrother);
        if ($uterine < 2 || $fullBrothers === 0) {
            return;
        }

        $sixth = Fraction::of(1, 6);
        $motherShare = $state->shareFor(HeirType::Mother)?->share;
        $grandmotherShare = Fraction::sum(array_map(
            static fn (Share $s) => $s->share,
            array_filter(
                $state->shares(),
                static fn (Share $s) => $s->heir === HeirType::MaternalGrandmother || $s->heir === HeirType::PaternalGrandmother,
            ),
        ));

        $maternalSixthPresent = ($motherShare !== null && $motherShare->equals($sixth))
            || $grandmotherShare->equals($sixth);
        if (!$maternalSixthPresent) {
            return;
        }

        $state->specialCase = 'mushtaraka';

        if (!$state->input->madhhab->mushtarakaSharesWithFullSiblings()) {
            $state->addWarning('mushtaraka_full_siblings_take_nothing');
            $state->exclude(HeirType::FullBrother, 'mushtaraka_full_siblings_take_nothing');
            $state->exclude(HeirType::FullSister, 'mushtaraka_full_siblings_take_nothing');

            return;
        }

        // The full siblings join the uterine siblings in their third, and the
        // third is then divided equally per head, male and female alike.
        $uterineShare = $state->shareFor(HeirType::UterineSibling);
        if ($uterineShare === null) {
            throw new CalculationError('Mushtaraka detected without a uterine share.');
        }

        $fullSisters = $state->live(HeirType::FullSister);
        $heads = $uterine + $fullBrothers + $fullSisters;
        $perHead = $uterineShare->share->divide(Fraction::of($heads));

        $state->setShare($uterineShare->withShare($perHead->multiply(Fraction::of($uterine))));
        $state->addShare(new Share(
            HeirType::FullBrother,
            $fullBrothers,
            $perHead->multiply(Fraction::of($fullBrothers)),
            'quranic',
            'mushtaraka_shares_the_third',
        ));
        if ($fullSisters > 0) {
            $state->addShare(new Share(
                HeirType::FullSister,
                $fullSisters,
                $perHead->multiply(Fraction::of($fullSisters)),
                'quranic',
                'mushtaraka_shares_the_third',
            ));
        }

        $state->addWarning('mushtaraka_sharing_applied');
    }

    // ---------------------------------------------------------------------
    // Residuary chain
    // ---------------------------------------------------------------------

    /** @return list<array{type:HeirType,count:int,partner:?HeirType,partnerCount:int,maAlGhayr:bool}> */
    private function residuaryChain(CalculationState $state): array
    {
        $chain = [];

        foreach (self::RESIDUARY_ORDER as [$type, $partner]) {
            // A sister made residuary by a daughter (asaba ma'a'l-ghayr) stands
            // exactly where her brother would have stood.
            if ($type === HeirType::FullBrother
                && $state->fullSistersAreResiduaryWithDaughters
                && $state->live(HeirType::FullBrother) === 0) {
                $chain[] = [
                    'type' => HeirType::FullSister,
                    'count' => $state->live(HeirType::FullSister),
                    'partner' => null,
                    'partnerCount' => 0,
                    'maAlGhayr' => true,
                ];
                continue;
            }
            if ($type === HeirType::ConsanguineBrother
                && $state->consanguineSistersAreResiduaryWithDaughters
                && $state->live(HeirType::ConsanguineBrother) === 0) {
                $chain[] = [
                    'type' => HeirType::ConsanguineSister,
                    'count' => $state->live(HeirType::ConsanguineSister),
                    'partner' => null,
                    'partnerCount' => 0,
                    'maAlGhayr' => true,
                ];
                continue;
            }

            $count = $state->live($type);
            if ($count === 0) {
                continue;
            }

            // With a male descendant the father and grandfather are sharers
            // only; the residue belongs to the descendant.
            if (($type === HeirType::Father || $type === HeirType::PaternalGrandfather)
                && $state->hasMaleDescendant) {
                continue;
            }

            $chain[] = [
                'type' => $type,
                'count' => $count,
                'partner' => $partner,
                'partnerCount' => $partner !== null ? $state->live($partner) : 0,
                'maAlGhayr' => false,
            ];
        }

        return $chain;
    }

    private function payResiduaries(CalculationState $state, Fraction $residue): bool
    {
        $chain = $this->residuaryChain($state);
        if ($chain === []) {
            return false;
        }

        if ($this->grandfatherCompetesWithSiblings($state)) {
            $this->applyZaydGrandfatherDoctrine($state, $residue, $chain);

            return true;
        }

        $first = $chain[0];
        $this->payResiduaryEntry($state, $first, $residue);
        $this->excludeLowerResiduaries($state, $chain, 1, $first['type']);

        return true;
    }

    /** @param array{type:HeirType,count:int,partner:?HeirType,partnerCount:int,maAlGhayr:bool} $entry */
    private function payResiduaryEntry(CalculationState $state, array $entry, Fraction $residue): void
    {
        if ($entry['maAlGhayr']) {
            $this->creditResiduary(
                $state,
                $entry['type'],
                $entry['count'],
                $residue,
                $entry['type']->value . '_residuary_with_daughter',
            );

            return;
        }

        $partnerCount = $entry['partner'] !== null ? $entry['partnerCount'] : 0;
        if ($partnerCount === 0) {
            $this->creditResiduary($state, $entry['type'], $entry['count'], $residue, $entry['type']->value . '_residuary');

            return;
        }

        // Male takes twice the female where both inherit together.
        $units = $entry['count'] * 2 + $partnerCount;
        $unit = $residue->divide(Fraction::of($units));

        $this->creditResiduary(
            $state,
            $entry['type'],
            $entry['count'],
            $unit->multiply(Fraction::of($entry['count'] * 2)),
            $entry['type']->value . '_residuary',
        );
        $this->creditResiduary(
            $state,
            $entry['partner'],
            $partnerCount,
            $unit->multiply(Fraction::of($partnerCount)),
            $entry['partner']->value . '_residuary_with_' . $entry['type']->value,
        );
    }

    private function creditResiduary(
        CalculationState $state,
        HeirType $type,
        int $count,
        Fraction $portion,
        string $reasonKey,
    ): void {
        $existing = $state->shareFor($type);
        if ($existing !== null) {
            // The father and the true grandfather can hold a sixth as a sharer
            // and the residue on top of it.
            $state->setShare(
                $existing->withShare($existing->share->add($portion))->withBasis('quranic_and_residuary')
            );

            return;
        }

        $state->addShare(new Share($type, $count, $portion, 'residuary', $reasonKey));
    }

    /**
     * @param list<array{type:HeirType,count:int,partner:?HeirType,partnerCount:int,maAlGhayr:bool}> $chain
     */
    private function excludeLowerResiduaries(CalculationState $state, array $chain, int $from, HeirType $by): void
    {
        for ($i = $from, $n = count($chain); $i < $n; $i++) {
            foreach ([$chain[$i]['type'], $chain[$i]['partner']] as $type) {
                if ($type === null || $state->shareFor($type) !== null) {
                    continue;
                }
                $state->exclude($type, 'excluded_by_' . $by->value, $by);
            }
        }
    }

    private function markResiduariesWithNothing(CalculationState $state, string $reasonKey): void
    {
        foreach ($this->residuaryChain($state) as $entry) {
            foreach ([$entry['type'], $entry['partner']] as $type) {
                if ($type === null || $state->shareFor($type) !== null) {
                    continue;
                }
                $state->exclude($type, $reasonKey);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Grandfather with brothers, outside the Hanafi school
    // ---------------------------------------------------------------------

    private function grandfatherCompetesWithSiblings(CalculationState $state): bool
    {
        return $state->siblingsCompeteWithGrandfather;
    }

    /**
     * Zayd b. Thabit's doctrine, followed by the Maliki, Shafi'i and Hanbali
     * schools: the grandfather takes whichever is best for him of sharing with
     * the brothers as though he were one of them (muqasama), one third of the
     * residue, or one sixth of the whole estate.
     *
     * NOT implemented, and reported as a warning instead: the mu'adda
     * reckoning, where consanguine siblings are counted against the grandfather
     * and then surrender to the full siblings.
     *
     * @param list<array{type:HeirType,count:int,partner:?HeirType,partnerCount:int,maAlGhayr:bool}> $chain
     */
    private function applyZaydGrandfatherDoctrine(CalculationState $state, Fraction $residue, array $chain): void
    {
        $siblingTypes = [
            HeirType::FullBrother, HeirType::FullSister,
            HeirType::ConsanguineBrother, HeirType::ConsanguineSister,
        ];

        if ($state->hasFemaleDescendantInheriting) {
            // Grandfather, brothers and an inheriting daughter together is a
            // genuinely contested configuration. Rather than guess, the engine
            // gives the residue to the grandfather and says so loudly.
            $state->addWarning('grandfather_with_siblings_and_daughters_not_modelled');
            $this->creditResiduary($state, HeirType::PaternalGrandfather, 1, $residue, 'grandfather_residuary');
            foreach ($siblingTypes as $type) {
                $state->exclude($type, 'excluded_by_grandfather', HeirType::PaternalGrandfather);
            }
            $this->excludeLowerResiduaries($state, $chain, 0, HeirType::PaternalGrandfather);

            return;
        }

        $brothers = $state->live(HeirType::FullBrother);
        $sisters = $state->live(HeirType::FullSister);
        $brotherType = HeirType::FullBrother;
        $sisterType = HeirType::FullSister;

        if ($brothers + $sisters === 0) {
            $brothers = $state->live(HeirType::ConsanguineBrother);
            $sisters = $state->live(HeirType::ConsanguineSister);
            $brotherType = HeirType::ConsanguineBrother;
            $sisterType = HeirType::ConsanguineSister;
        } elseif ($state->live(HeirType::ConsanguineBrother) + $state->live(HeirType::ConsanguineSister) > 0) {
            $state->addWarning('muadda_doctrine_not_implemented');
        }

        $siblingUnits = $brothers * 2 + $sisters;
        $muqasama = $residue->multiply(Fraction::of(2, 2 + $siblingUnits));
        $thirdOfResidue = $residue->divide(Fraction::of(3));
        $sixthOfEstate = Fraction::of(1, 6);

        $grandfatherShare = Fraction::max($muqasama, $thirdOfResidue, $sixthOfEstate);
        if ($grandfatherShare->isGreaterThan($residue)) {
            $grandfatherShare = $residue;
        }

        $this->creditResiduary($state, HeirType::PaternalGrandfather, 1, $grandfatherShare, 'grandfather_with_siblings_zayd');

        $siblingResidue = $residue->subtract($grandfatherShare);
        if ($siblingResidue->isPositive() && $siblingUnits > 0) {
            $unit = $siblingResidue->divide(Fraction::of($siblingUnits));
            if ($brothers > 0) {
                $state->addShare(new Share(
                    $brotherType,
                    $brothers,
                    $unit->multiply(Fraction::of($brothers * 2)),
                    'residuary',
                    $brotherType->value . '_shares_with_grandfather',
                ));
            }
            if ($sisters > 0) {
                $state->addShare(new Share(
                    $sisterType,
                    $sisters,
                    $unit->multiply(Fraction::of($sisters)),
                    'residuary',
                    $sisterType->value . '_shares_with_grandfather',
                ));
            }
        } else {
            foreach ([$brotherType, $sisterType] as $type) {
                $state->exclude($type, 'no_residue_after_grandfather', HeirType::PaternalGrandfather);
            }
        }

        foreach ($siblingTypes as $type) {
            if ($state->shareFor($type) === null) {
                $state->exclude($type, 'excluded_by_full_sibling_or_grandfather', HeirType::PaternalGrandfather);
            }
        }

        $state->addWarning('zayd_grandfather_doctrine_applied');
        $this->excludeLowerResiduaries($state, $chain, 0, HeirType::PaternalGrandfather);
    }
}
