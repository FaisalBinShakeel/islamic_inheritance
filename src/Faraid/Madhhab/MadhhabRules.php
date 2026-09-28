<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

use Faraid\Exception\InvalidInput;

/**
 * The Sunni schools agree on the overwhelming majority of cases. This class
 * holds only the points where they diverge; everything else lives in the
 * single shared engine. Separate engines would mean separate places for a bug
 * to hide.
 *
 * Alongside the four schools there is one further option, Ahl-e-Hadith, for
 * those who do not follow a school taqlidan. It is not a fifth madhhab and
 * isSchoolOfLaw() says so, but the same strategy layer is the right place to
 * hold its positions.
 *
 * Every flag below is a documented point of difference, and every one of them
 * is pending review by someone qualified in that position. See
 * docs/METHODOLOGY.md and docs/REVIEW-CHECKLIST.md.
 */
abstract class MadhhabRules
{
    abstract public function key(): string;

    abstract public function name(): string;

    /**
     * False for Ahl-e-Hadith, who do not regard themselves as following a
     * school. The interface uses this to word the label correctly rather than
     * calling everything a madhhab.
     */
    public function isSchoolOfLaw(): bool
    {
        return true;
    }

    /**
     * Grandfather competing with full or consanguine brothers.
     *
     * Abu Hanifa: the grandfather excludes them entirely.
     * Maliki, Shafi'i, Hanbali (and the two companions of Abu Hanifa): they
     * share with him under Zayd b. Thabit's doctrine.
     */
    abstract public function grandfatherExcludesSiblings(): bool;

    /**
     * The Mushtaraka / Himariyya case: husband + mother (or grandmother) +
     * two or more uterine siblings + full brother(s).
     *
     * false — the full brothers take nothing (Ali's view).
     * true  — they share the uterine siblings' one third (Umar's view).
     */
    abstract public function mushtarakaSharesWithFullSiblings(): bool;

    /**
     * Radd: returning the surplus to the fixed-share heirs when the shares
     * sum to less than unity and no residuary survives.
     */
    abstract public function appliesRadd(): bool;

    /**
     * The Akdariyya case: husband + mother + true grandfather + one sister,
     * and nothing else. It cannot arise under Hanafi rules, because the
     * grandfather has already excluded the sister.
     */
    public function appliesAkdariyya(): bool
    {
        return !$this->grandfatherExcludesSiblings();
    }

    /**
     * Whether a spouse may receive the surplus by radd when there is no other
     * heir at all. Classically the surplus goes to the public treasury; this
     * is a configuration point, not a school's doctrine, and defaults to off
     * so that the output says plainly that a remainder was left undistributed.
     */
    public function raddToSpouseWhenSoleHeir(): bool
    {
        return false;
    }

    /** @return list<string> Divergences worth stating on the result itself. */
    abstract public function standingNotes(): array;

    public static function fromKey(string $key): self
    {
        return match (strtolower(trim($key))) {
            'hanafi' => new Hanafi(),
            'shafii', "shafi'i", 'shafi' => new Shafii(),
            'maliki' => new Maliki(),
            'hanbali' => new Hanbali(),
            'ahl_e_hadith', 'ahle_hadith', 'ahl-e-hadith', 'salafi', 'ghair_muqallid' => new AhlAlHadith(),
            default => throw new InvalidInput(sprintf(
                'Unknown madhhab "%s". Expected one of: hanafi, shafii, maliki, hanbali, ahl_e_hadith.',
                $key
            )),
        };
    }

    /** Every selectable position, in the order the interface offers them. */
    public static function keys(): array
    {
        return ['hanafi', 'shafii', 'maliki', 'hanbali', 'ahl_e_hadith'];
    }

    /** The four schools alone, for text that speaks specifically of them. */
    public static function schoolKeys(): array
    {
        return ['hanafi', 'shafii', 'maliki', 'hanbali'];
    }
}
