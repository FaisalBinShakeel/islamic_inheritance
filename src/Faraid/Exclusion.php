<?php

declare(strict_types=1);

namespace Faraid;

/**
 * An heir who survives but takes nothing, and who excluded them.
 *
 * Relatives always ask this question, so the engine records it rather than
 * letting the heir quietly vanish from the output.
 */
final class Exclusion
{
    public function __construct(
        public readonly HeirType $heir,
        public readonly int $count,
        /** Translation key, e.g. excluded_by_son. */
        public readonly string $reasonKey,
        /** hajb_hirman (removed entirely) | disqualification */
        public readonly string $kind = 'hajb_hirman',
        public readonly ?HeirType $excludedBy = null,
    ) {
    }
}
