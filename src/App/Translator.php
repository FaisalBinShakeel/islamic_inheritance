<?php

declare(strict_types=1);

namespace App;

/**
 * Flat key/value translations, loaded per locale from resources/lang.
 *
 * The engine returns a reason_key for every share and every exclusion and
 * knows nothing about language, so the same calculation renders in any locale
 * by looking those keys up here.
 */
final class Translator
{
    /** @var array<string,array<string,string>> */
    private static array $loaded = [];

    /** @var list<string> keys asked for but not found, for the missing-strings check */
    private static array $missing = [];

    public static function load(string $locale): array
    {
        if (isset(self::$loaded[$locale])) {
            return self::$loaded[$locale];
        }

        $path = dirname(__DIR__, 2) . '/resources/lang/' . $locale . '.php';
        $strings = is_file($path) ? (array) require $path : [];

        return self::$loaded[$locale] = $strings;
    }

    /** @param array<string,string|int|float> $replacements */
    public static function get(string $key, array $replacements = [], ?string $locale = null): string
    {
        $locale = $locale ?? Locale::active();
        $strings = self::load($locale);

        $value = $strings[$key] ?? null;
        if ($value === null && $locale !== 'en') {
            // Fall back to English rather than showing a raw key to a reader.
            $value = self::load('en')[$key] ?? null;
        }

        if ($value === null) {
            self::$missing[] = $locale . ':' . $key;

            return $key;
        }

        foreach ($replacements as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }

    public static function has(string $key, ?string $locale = null): bool
    {
        return isset(self::load($locale ?? Locale::active())[$key]);
    }

    /** @return list<string> */
    public static function missing(): array
    {
        return array_values(array_unique(self::$missing));
    }
}
