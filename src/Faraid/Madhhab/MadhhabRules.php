<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

use Faraid\Exception\InvalidInput;

/**
 * The four Sunni schools agree on the overwhelming majority of cases. This
 * class holds only the points where they diverge; everything else lives in
 * the single shared engine. Four engines would mean four places for a bug to
 * hide.
 *
 * Every flag below is a documented point of difference, and every one of them
 * is pending review by a qualified scholar of that school. See
 * docs/METHODOLOGY.md and docs/REVIEW-CHECKLIST.md.
 */
abstract class MadhhabRules
{
    abstract public function key(): string;

    abstract public function name(): string;

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
            default => throw new InvalidInput(sprintf(
                'Unknown madhhab "%s". Expected one of: hanafi, shafii, maliki, hanbali.',
                $key
            )),
        };
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return ['hanafi', 'shafii', 'maliki', 'hanbali'];
    }
}
