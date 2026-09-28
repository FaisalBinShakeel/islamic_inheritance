<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config;
use App\Locale;
use App\Repository\PostRepository;
use App\Response;

/**
 * sitemap.xml and robots.txt, generated rather than kept as files, so lastmod
 * is always the post's real updated_at and a new locale needs no extra work.
 */
final class FeedController
{
    public function sitemap(): Response
    {
        $entries = [];

        foreach (Locale::enabled() as $locale) {
            $entries[] = [
                'loc' => Locale::path('', $locale),
                'lastmod' => PostRepository::lastUpdated() ?? gmdate('Y-m-d H:i:s'),
                'priority' => '1.0',
                'changefreq' => 'weekly',
            ];
            $entries[] = [
                'loc' => Locale::path('blog', $locale),
                'lastmod' => PostRepository::lastUpdated() ?? gmdate('Y-m-d H:i:s'),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        foreach (PostRepository::sitemapEntries() as $entry) {
            $entries[] = $entry + ['priority' => '0.7', 'changefreq' => 'monthly'];
        }

        // Category and tag archives are real pages people land on, so they
        // belong in the sitemap. An archive with nothing behind it does not
        // exist — the controller returns 404 for those — so only archives
        // with published posts are listed here.
        $lastmod = PostRepository::lastUpdated() ?? gmdate('Y-m-d H:i:s');
        $active = Locale::active();

        foreach (Locale::enabled() as $locale) {
            Locale::setActive($locale);

            foreach (PostRepository::categories($locale) as $category) {
                if ((int) ($category['post_count'] ?? 0) === 0) {
                    continue;
                }
                $entries[] = [
                    'loc' => Locale::path('blog/category/' . $category['slug'], $locale),
                    'lastmod' => $lastmod,
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                ];
            }

            foreach (PostRepository::tagsWithPosts($locale) as $tag) {
                $entries[] = [
                    'loc' => Locale::path('blog/tag/' . $tag['slug'], $locale),
                    'lastmod' => $lastmod,
                    'priority' => '0.4',
                    'changefreq' => 'monthly',
                ];
            }
        }

        Locale::setActive($active);

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . htmlspecialchars(Config::url($entry['loc']), ENT_XML1) . '</loc>';
            $xml[] = '    <lastmod>' . htmlspecialchars(self::date($entry['lastmod']), ENT_XML1) . '</lastmod>';
            $xml[] = '    <changefreq>' . $entry['changefreq'] . '</changefreq>';
            $xml[] = '    <priority>' . $entry['priority'] . '</priority>';
            $xml[] = '  </url>';
        }
        $xml[] = '</urlset>';

        return Response::text(implode("\n", $xml), 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /install.php',
            '',
            'Sitemap: ' . Config::url('/sitemap.xml'),
        ];

        return Response::text(implode("\n", $lines) . "\n");
    }

    private static function date(string $stored): string
    {
        $timestamp = strtotime($stored . ' UTC');

        return $timestamp === false ? gmdate('c') : gmdate('c', $timestamp);
    }
}
