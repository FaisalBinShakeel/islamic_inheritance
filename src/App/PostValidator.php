<?php

declare(strict_types=1);

namespace App;

use App\Repository\PostRepository;
use App\Support\Str;

/**
 * The publish gate.
 *
 * These are the four bugs the product specification found on a sibling site,
 * turned into code-level guarantees: a title that diverges from the H1, a
 * hand-typed canonical, a dead internal link, and a missing meta description.
 * A post that trips any of the blocking rules cannot be published at all.
 */
final class PostValidator
{
    public const TITLE_LIMIT = 60;
    public const META_LIMIT = 160;
    public const META_WARN = 155;

    /** @return array{errors:list<string>,warnings:list<string>} */
    public static function check(array $post, ?int $exceptId = null): array
    {
        $errors = [];
        $warnings = [];

        $locale = (string) ($post['locale'] ?? 'en');
        $title = trim((string) ($post['title'] ?? ''));
        $h1 = trim((string) ($post['h1'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        $meta = trim((string) ($post['meta_description'] ?? ''));
        $body = (string) ($post['body'] ?? '');
        $keyword = trim((string) ($post['target_keyword'] ?? ''));
        $publishing = ($post['status'] ?? 'draft') === 'published';

        if ($title === '') {
            $errors[] = 'The title is required.';
        }
        if ($h1 === '') {
            $errors[] = 'The H1 is required. Leave it blank in the form and it will copy the title.';
        }
        if ($slug === '') {
            $errors[] = 'The slug is required.';
        } elseif ($slug !== Str::slug($slug)) {
            $errors[] = sprintf('The slug must be lowercase and hyphenated. Suggested: %s', Str::slug($slug));
        } elseif (PostRepository::slugExists($slug, $locale, $exceptId)) {
            $errors[] = sprintf('Another %s post already uses the slug "%s".', $locale, $slug);
        }

        if ($meta === '') {
            $errors[] = 'The meta description is required.';
        } elseif (mb_strlen($meta) > self::META_LIMIT) {
            $errors[] = sprintf('The meta description is %d characters; the limit is %d.', mb_strlen($meta), self::META_LIMIT);
        } elseif (mb_strlen($meta) > self::META_WARN) {
            $warnings[] = sprintf('The meta description is %d characters and may be truncated in search results.', mb_strlen($meta));
        }

        $withBrand = $title . ' — ' . Config::siteName();
        if (mb_strlen($withBrand) > self::TITLE_LIMIT && !str_contains($title, Config::siteName())) {
            $warnings[] = sprintf(
                'The title plus the brand suffix is %d characters, so the suffix will be dropped. Shorten the title to keep it.',
                mb_strlen($withBrand)
            );
        }

        if ($title !== '' && $h1 !== '' && $title !== $h1) {
            // Allowed, but never silently: this is the exact divergence the
            // specification asked to be surfaced at the point of entry.
            $warnings[] = 'The title and the H1 differ. That is sometimes deliberate — make sure it is here.';
        }

        if ($keyword !== '') {
            $clash = PostRepository::keywordTakenBy($keyword, $locale, $exceptId);
            if ($clash !== null) {
                $errors[] = sprintf(
                    'The keyword "%s" is already the target of "%s". Two posts must not chase the same primary phrase.',
                    $keyword,
                    (string) $clash['title']
                );
            }
        } elseif ($publishing && ($post['category_slug'] ?? null) !== Repository\PostRepository::PAGE_CATEGORY) {
            // Trust pages are not chasing a search phrase, so they are not
            // expected in the keyword register.
            $warnings[] = 'No target keyword was recorded, so this post is missing from the keyword register.';
        }

        if ($body === '' || mb_strlen(strip_tags($body)) < 200) {
            $warnings[] = 'The body is very short.';
        }

        foreach (self::deadLinks($body, $locale) as $dead) {
            $errors[] = sprintf('The internal link %s does not resolve to a page.', $dead);
        }

        if (!self::linksToCalculator($body, $locale)) {
            $warnings[] = 'This article does not link to the calculator. Every guide should, in the first third of the page.';
        }

        if (preg_match('~<img(?![^>]*\salt=)[^>]*>~i', $body) === 1) {
            $errors[] = 'An image has no alt text.';
        }

        if (preg_match('~<h1[\s>]~i', $body) === 1) {
            $errors[] = 'The body contains an H1. The layout renders the only H1 on the page.';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /** @return list<string> internal hrefs that would return a 404 */
    public static function deadLinks(string $body, string $locale): array
    {
        $dead = [];
        foreach (Str::internalLinks($body) as $href) {
            if (!self::resolves($href)) {
                $dead[] = $href;
            }
        }

        return $dead;
    }

    /** Does this internal path lead anywhere? */
    public static function resolves(string $href): bool
    {
        $path = parse_url($href, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }

        $split = Router::splitLocale($path);
        $locale = $split['locale'];
        $rest = trim($split['path'], '/');

        if ($rest === '' || $rest === 'blog' || $rest === 'sitemap.xml' || $rest === 'robots.txt' || $rest === 'report') {
            return true;
        }
        if (str_starts_with($rest, 'assets/')) {
            return is_file(dirname(__DIR__, 2) . '/public/' . $rest);
        }
        if (str_starts_with($rest, 'blog/category/')) {
            return PostRepository::category(substr($rest, strlen('blog/category/')), $locale) !== null;
        }
        if (str_starts_with($rest, 'blog/')) {
            return PostRepository::findBySlug(substr($rest, strlen('blog/')), $locale) !== null;
        }

        if (in_array($rest, Kernel::TRUST_PAGES, true)) {
            return PostRepository::findBySlug($rest, $locale) !== null;
        }

        return false;
    }

    private static function linksToCalculator(string $body, string $locale): bool
    {
        $home = Locale::path('', $locale);
        foreach (Str::internalLinks($body) as $href) {
            if (rtrim($href, '/') === rtrim($home, '/') || $href === $home) {
                return true;
            }
        }

        return false;
    }
}
