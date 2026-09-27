<?php

declare(strict_types=1);

namespace Faraid\Madhhab;

final class Shafii extends MadhhabRules
{
    public function key(): string
    {
        return 'shafii';
    }

    public function name(): string
    {
        return "Shafi'i";
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
        // Classically the surplus goes to the public treasury (bayt al-mal).
        // Where no functioning bayt al-mal exists, later and modern Shafi'i
        // practice applies radd, which is what the engine does. Flagged on the
        // result so the user is never left unaware of the choice.
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
