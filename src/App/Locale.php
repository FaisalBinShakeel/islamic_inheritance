<?php

declare(strict_types=1);

namespace App;

/**
 * The locale registry.
 *
 * English is served at the site root and every other locale sits in a
 * subfolder (/ur/, /ar/, /fr/, /id/). Subfolders pool ranking authority into
 * one domain; separate country domains would split it and force the same
 * authority to be built several times over.
 */
final class Locale
{
    private static string $active = 'en';

    /** @var array<string,array{name:string,native:string,dir:string,hreflang:string,madhhab:?string}> */
    private const DEFINITIONS = [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'hreflang' => 'en', 'madhhab' => null],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'dir' => 'rtl', 'hreflang' => 'ur-PK', 'madhhab' => 'hanafi'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl', 'hreflang' => 'ar', 'madhhab' => null],
        'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr', 'hreflang' => 'fr-FR', 'madhhab' => 'maliki'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'dir' => 'ltr', 'hreflang' => 'id-ID', 'madhhab' => 'shafii'],
    ];

    /** @return list<string> locales that are switched on in config */
    public static function enabled(): array
    {
        $enabled = [];
        foreach (Config::locales() as $code) {
            if (isset(self::DEFINITIONS[$code])) {
                $enabled[] = $code;
            }
        }

        return $enabled === [] ? ['en'] : $enabled;
    }

    public static function exists(string $code): bool
    {
        return in_array($code, self::enabled(), true);
    }

    public static function active(): string
    {
        return self::$active;
    }

    public static function setActive(string $code): void
    {
        self::$active = self::exists($code) ? $code : 'en';
    }

    public static function direction(?string $code = null): string
    {
        return self::DEFINITIONS[$code ?? self::$active]['dir'] ?? 'ltr';
    }

    public static function isRtl(?string $code = null): bool
    {
        return self::direction($code) === 'rtl';
    }

    public static function hreflang(string $code): string
    {
        return self::DEFINITIONS[$code]['hreflang'] ?? $code;
    }

    public static function nativeName(string $code): string
    {
        return self::DEFINITIONS[$code]['native'] ?? $code;
    }

    public static function englishName(string $code): string
    {
        return self::DEFINITIONS[$code]['name'] ?? $code;
    }

    /** The madhhab a locale suggests. A suggestion only — the user still chooses. */
    public static function suggestedMadhhab(?string $code = null): ?string
    {
        return self::DEFINITIONS[$code ?? self::$active]['madhhab'] ?? null;
    }

    /** Site path for a locale: '' for English, 'ur' otherwise. */
    public static function prefix(?string $code = null): string
    {
        $code ??= self::$active;

        return $code === 'en' ? '' : $code;
    }

    /** Build a path in a locale: path('blog', 'ur') === '/ur/blog'. */
    public static function path(string $path = '', ?string $code = null): string
    {
        $prefix = self::prefix($code);
        $path = trim($path, '/');

        if ($prefix === '') {
            return '/' . $path;
        }

        return $path === '' ? '/' . $prefix . '/' : '/' . $prefix . '/' . $path;
    }
}
