<?php

declare(strict_types=1);

namespace Faraid\Tests\Support;

use Faraid\Fraction;

/**
 * One scholar-checkable case: an input, the answer it must produce, and where
 * that answer comes from.
 *
 * A test with no source is not a test, so `source` is required and the loader
 * rejects any case that omits it. `verifiedBy` names the scholar who has
 * confirmed the expected answer in writing; until that happens it is null and
 * the suite reports the case as unverified.
 */
final class TestCaseDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $group,
        public readonly string $source,
        public readonly ?string $verifiedBy,
        public readonly array $input,
        public readonly array $expect,
        public readonly ?string $expectError,
        public readonly string $file,
    ) {
    }

    public static function fromArray(array $raw, string $file): self
    {
        foreach (['id', 'title', 'source'] as $required) {
            if (!isset($raw[$required]) || trim((string) $raw[$required]) === '') {
                throw new \InvalidArgumentException(sprintf(
                    'Case in %s is missing "%s". A test with no source is not a test.',
                    $file,
                    $required
                ));
            }
        }

        if (!isset($raw['input']) || !is_array($raw['input'])) {
            throw new \InvalidArgumentException(sprintf('Case "%s" has no input array.', $raw['id']));
        }

        $expect = $raw['expect'] ?? [];
        $expectError = $raw['expect_error'] ?? null;
        if ($expect === [] && $expectError === null) {
            throw new \InvalidArgumentException(sprintf('Case "%s" asserts nothing.', $raw['id']));
        }

        return new self(
            (string) $raw['id'],
            (string) $raw['title'],
            (string) ($raw['group'] ?? 'ungrouped'),
            (string) $raw['source'],
            isset($raw['verified_by']) ? (string) $raw['verified_by'] : null,
            $raw['input'],
            $expect,
            $expectError === null ? null : (string) $expectError,
            $file,
        );
    }

    public static function parseFraction(string $value): Fraction
    {
        $value = trim($value);
        if (!preg_match('~^(-?\d+)(?:\s*/\s*(\d+))?$~', $value, $m)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a fraction.', $value));
        }

        return new Fraction((int) $m[1], isset($m[2]) ? (int) $m[2] : 1);
    }
}
