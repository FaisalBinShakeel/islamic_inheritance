<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

final class Hanafi extends MadhhabRules
{
    public function key(): string
    {
        return 'hanafi';
    }

    public function name(): string
    {
        return 'Hanafi';
    }

    public function grandfatherExcludesSiblings(): bool
    {
        // Abu Hanifa's own view, which the engine takes as the Hanafi default.
        // His two companions (Abu Yusuf and Muhammad al-Shaybani) hold that the
        // brothers share with the grandfather; documented, not implemented.
        return true;
    }

    public function mushtarakaSharesWithFullSiblings(): bool
    {
        return false;
    }

    public function appliesRadd(): bool
    {
        return true;
    }

    public function standingNotes(): array
    {
        return [
            'madhhab_hanafi_grandfather_excludes_siblings',
            'madhhab_hanafi_radd_excludes_spouse',
        ];
    }
}
