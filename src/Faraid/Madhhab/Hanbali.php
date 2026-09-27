<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

final class Hanbali extends MadhhabRules
{
    public function key(): string
    {
        return 'hanbali';
    }

    public function name(): string
    {
        return 'Hanbali';
    }

    public function grandfatherExcludesSiblings(): bool
    {
        return false;
    }

    public function mushtarakaSharesWithFullSiblings(): bool
    {
        // OPEN QUESTION, flagged for the reviewing scholar.
        //
        // The classical Hanbali position follows Ali's view: the full brothers
        // take nothing, the same outcome as Hanafi. The product specification
        // groups Hanbali with Shafi'i as sharing the one third instead. The
        // engine implements the classical position and surfaces the conflict
        // as a warning rather than resolving it silently. See
        // docs/REVIEW-CHECKLIST.md, item "Mushtaraka (Hanbali)".
        return false;
    }

    public function appliesRadd(): bool
    {
        return true;
    }

    public function standingNotes(): array
    {
        return [
            'madhhab_grandfather_shares_with_siblings',
            'madhhab_hanbali_mushtaraka_under_review',
        ];
    }
}
