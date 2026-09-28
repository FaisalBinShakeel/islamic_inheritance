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
        // The surplus goes to the public treasury (bayt al-mal); nobody
        // receives more than their allotted share. Ibn Qudamah, al-Mughni
        // 6/186, attributes this to Zayd b. Thabit, and to Malik, al-Awza'i
        // and al-Shafi'i.
        //
        // An earlier version of this engine applied radd here on the grounds
        // that modern practice commonly does. That was reporting a practice
        // rather than the school's position, which is the opposite of what
        // this calculator is for. Where no functioning treasury exists, what
        // should happen to the remainder is a question for a scholar, and the
        // result now says so instead of quietly assigning it.
        return false;
    }

    public function standingNotes(): array
    {
        return [
            'madhhab_grandfather_shares_with_siblings',
            'madhhab_surplus_to_bayt_al_mal',
        ];
    }
}
