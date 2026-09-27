<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    /** URL slug: lowercase, hyphenated, ASCII where possible, script preserved otherwise. */
    public static function slug(string $value): string
    {
        $value = trim($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && preg_match('~[a-zA-Z0-9]~', $ascii) === 1) {
            $value = $ascii;
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('~[^\p{L}\p{N}]+~u', '-', $value) ?? '';

        return trim($value, '-') ?: 'post';
    }

    public static function excerpt(string $html, int $length = 160): string
    {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags($html)) ?? '');
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace === false ? $cut : mb_substr($cut, 0, $lastSpace), " ,.;:") . '…';
    }

    /** Reading time in minutes, at 200 words per minute, minimum one. */
    public static function readingMinutes(string $html): int
    {
        $words = str_word_count(strip_tags($html));
        if ($words === 0) {
            // Non-Latin scripts: fall back to character count.
            $words = (int) ceil(mb_strlen(strip_tags($html)) / 5);
        }

        return max(1, (int) ceil($words / 200));
    }

    /** @return list<string> every internal href in a block of HTML */
    public static function internalLinks(string $html): array
    {
        if (preg_match_all('~<a\s[^>]*href=["\']([^"\']+)["\']~i', $html, $matches) === false) {
            return [];
        }

        $links = [];
        foreach ($matches[1] ?? [] as $href) {
            if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
                $links[] = strtok($href, '#') ?: $href;
            }
        }

        return array_values(array_unique($links));
    }

    public static function money(float $amount, string $currency = ''): string
    {
        $formatted = number_format($amount, 2);
        $formatted = str_contains($formatted, '.00') ? substr($formatted, 0, -3) : $formatted;

        return trim($currency . ' ' . $formatted);
    }
}
