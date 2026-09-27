<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

final class Maliki extends MadhhabRules
{
    public function key(): string
    {
        return 'maliki';
    }

    public function name(): string
    {
        return 'Maliki';
    }

    public function grandfatherExcludesSiblings(): bool
    {
        return false;
    }

    public function mushtarakaSharesWithFullSiblings(): bool
    {
        return true;
    }

    public function appliesRadd(): bool
    {
        // Same position as the Shafi'i entry above: classically no radd, but
        // modern practice commonly applies it. Reviewer must confirm.
        return true;
    }

    public function standingNotes(): array
    {
        return [
            'madhhab_grandfather_shares_with_siblings',
            'madhhab_radd_modern_practice_not_classical',
        ];
    }
}
