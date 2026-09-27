<?php

declare(strict_types=1);

namespace Faraid\Tests\Support;

/** Loads every case file under tests/Cases and hands back the definitions. */
final class CaseRegistry
{
    /** @return list<TestCaseDefinition> */
    public static function all(): array
    {
        $cases = [];
        $seen = [];

        $files = glob(__DIR__ . '/../Cases/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $raw = require $file;
            if (!is_array($raw)) {
                throw new \RuntimeException(sprintf('%s did not return an array of cases.', $file));
            }
            foreach ($raw as $entry) {
                $case = TestCaseDefinition::fromArray($entry, basename($file));
                if (isset($seen[$case->id])) {
                    throw new \RuntimeException(sprintf('Duplicate case id "%s".', $case->id));
                }
                $seen[$case->id] = true;
                $cases[] = $case;
            }
        }

        return $cases;
    }
}
