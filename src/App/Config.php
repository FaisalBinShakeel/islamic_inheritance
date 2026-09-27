<?php

declare(strict_types=1);

namespace App;

/**
 * Configuration, and the single source of truth for the site's origin.
 *
 * Canonical URLs, og:url and sitemap entries are all built by Config::url(),
 * never stored per page and never typed by hand. That is what makes a wrong
 * canonical structurally impossible rather than a thing someone has to
 * remember.
 */
final class Config
{
    private static ?array $values = null;
    private static ?string $path = null;

    public static function load(?string $path = null): void
    {
        self::$path = $path ?? dirname(__DIR__, 2) . '/config.php';
        self::$values = is_file(self::$path) ? (array) require self::$path : [];
    }

    public static function isInstalled(): bool
    {
        if (self::$values === null) {
            self::load();
        }

        return isset(self::$values['db']['name'], self::$values['url']) && self::$values['db']['name'] !== '';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$values === null) {
            self::load();
        }

        $value = self::$values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function origin(): string
    {
        return rtrim((string) self::get('url', ''), '/');
    }

    /** Absolute canonical URL for a site path. */
    public static function url(string $path = '/'): string
    {
        if ($path === '' || $path === '/') {
            return self::origin() . '/';
        }

        return self::origin() . '/' . ltrim($path, '/');
    }

    public static function siteName(): string
    {
        return (string) self::get('site_name', 'Wirasat Calculator');
    }

    /** @return list<string> */
    public static function locales(): array
    {
        $locales = self::get('locales', ['en']);

        return is_array($locales) && $locales !== [] ? array_values($locales) : ['en'];
    }

    public static function debug(): bool
    {
        return (bool) self::get('debug', false);
    }

    public static function write(string $path, array $values): void
    {
        $export = var_export($values, true);
        $contents = "<?php\n\n/**\n * Written by the installer. Safe to edit by hand.\n */\n\nreturn " . $export . ";\n";

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new \RuntimeException('Could not write ' . $path);
        }
        @chmod($path, 0640);
    }
}
