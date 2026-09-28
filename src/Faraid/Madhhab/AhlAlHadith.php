<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

/**
 * Ahl-e-Hadith / Ghair Muqallid — those who do not follow one of the four
 * schools taqlidan and derive directly from the Qur'an and the Sunnah.
 *
 * Strictly speaking this is not a fifth madhhab, and its adherents would not
 * call it one. It sits here because the engine's strategy layer is the right
 * place to hold "which position is taken where the evidence is read
 * differently", and because a Pakistani audience asks for it by name.
 *
 * Inheritance is mostly explicit text — the fractions are in the Qur'an — so
 * on the overwhelming majority of estates this produces exactly the same
 * figures as all four schools. It differs only at the small number of points
 * the schools themselves dispute, and each of those choices is recorded below
 * with the basis it rests on.
 *
 * ────────────────────────────────────────────────────────────────────────
 * NOT VERIFIED. These are the positions most commonly attributed to scholars
 * of this orientation. Nobody qualified in it has confirmed them for this
 * calculator, and no fatwa reference is claimed. Every result produced under
 * this option says so, and docs/REVIEW-CHECKLIST.md lists what has to be
 * settled. Do not present this as their ruling until it has been checked.
 * ────────────────────────────────────────────────────────────────────────
 */
final class AhlAlHadith extends MadhhabRules
{
    public function key(): string
    {
        return 'ahl_e_hadith';
    }

    public function name(): string
    {
        return 'Ahl-e-Hadith';
    }

    public function isSchoolOfLaw(): bool
    {
        return false;
    }

    public function grandfatherExcludesSiblings(): bool
    {
        // The grandfather is treated as standing in the father's place, and so
        // excludes the brothers. This is the position reported from Abu Bakr
        // and from Ibn Abbas, against Zayd b. Thabit's muqasama, and it is the
        // reading later preferred by a number of scholars who argue from the
        // reports directly rather than from a school's settled doctrine.
        //
        // PENDING VERIFICATION — see docs/REVIEW-CHECKLIST.md.
        return true;
    }

    public function mushtarakaSharesWithFullSiblings(): bool
    {
        // The position of Ali b. Abi Talib: the fixed shares have consumed the
        // estate, so the full brothers, who inherit only as residuaries, take
        // nothing. The same outcome as Hanafi.
        //
        // PENDING VERIFICATION — the contrary report from Umar is equally well
        // known, and which is preferred here is exactly what a qualified
        // reviewer has to settle.
        return false;
    }

    public function appliesRadd(): bool
    {
        return true;
    }

    public function raddToSpouseWhenSoleHeir(): bool
    {
        // Where a husband or wife is the only surviving heir, the surplus goes
        // to them rather than being left undistributed. This rests on the
        // practice reported from Uthman b. Affan, and is commonly cited today
        // on the reasoning that there is no functioning bayt al-mal to receive
        // it. The four schools in this calculator do not do this.
        //
        // PENDING VERIFICATION.
        return true;
    }

    public function standingNotes(): array
    {
        return [
            'madhhab_ahl_e_hadith_not_verified',
            'madhhab_ahl_e_hadith_grandfather_excludes_siblings',
            'madhhab_ahl_e_hadith_radd_may_reach_spouse',
            'madhhab_ahl_e_hadith_awl_minority_view_not_applied',
        ];
    }
}
