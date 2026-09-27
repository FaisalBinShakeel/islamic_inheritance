<?php

declare(strict_types=1);

use App\Config;
use App\Locale;
use App\Translator;

if (!function_exists('e')) {
    /** Escape for HTML. Everything printed in a template goes through this. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('t')) {
    function t(string $key, array $replacements = [], ?string $locale = null): string
    {
        return Translator::get($key, $replacements, $locale);
    }
}

if (!function_exists('lp')) {
    /** A path in the active locale: lp('blog') === '/blog' or '/ur/blog'. */
    function lp(string $path = '', ?string $locale = null): string
    {
        return Locale::path($path, $locale);
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = '/'): string
    {
        return Config::url($path);
    }
}

if (!function_exists('asset')) {
    /** Asset URL with a cache-busting stamp from the file's own mtime. */
    function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $file = dirname(__DIR__, 2) . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '1';

        return $path . '?v=' . $version;
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $stored, ?string $locale = null): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        $timestamp = strtotime($stored . ' UTC');
        if ($timestamp === false) {
            return $stored;
        }

        return gmdate('j F Y', $timestamp);
    }
}
