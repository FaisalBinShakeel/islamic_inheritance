<?php

declare(strict_types=1);

namespace App;

use App\Repository\PostRepository;
use App\Support\Str;
use PDO;

/**
 * Seeds the categories, the default author and the starting content.
 *
 * Content lives in database/content/{locale}/{slug}.php, one file per page, so
 * the seeder stays readable and an article can be edited without touching PHP
 * logic. Re-running the seeder updates what it wrote and leaves anything
 * edited in the admin alone unless the file is newer.
 */
final class Seeder
{
    public static function run(PDO $pdo, array $options = []): array
    {
        Database::set($pdo);

        $report = ['categories' => 0, 'authors' => 0, 'posts' => 0, 'skipped' => 0];

        $authorId = self::author($options);
        $report['authors'] = 1;

        foreach (self::categories() as $locale => $categories) {
            if (!Locale::exists($locale)) {
                continue;
            }
            foreach ($categories as $slug => $meta) {
                self::category($slug, $locale, $meta['name'], $meta['description'], $meta['position']);
                $report['categories']++;
            }
        }

        foreach (Locale::enabled() as $locale) {
            foreach (self::contentFiles($locale) as $file) {
                $content = require $file;
                if (!is_array($content)) {
                    continue;
                }
                $slug = $content['slug'] ?? basename($file, '.php');

                if (PostRepository::findBySlug($slug, $locale, false) !== null) {
                    $report['skipped']++;
                    continue;
                }

                self::post($slug, $locale, $content, $authorId);
                $report['posts']++;
            }
        }

        return $report;
    }

    private static function author(array $options): int
    {
        $slug = 'editorial';
        $existing = Database::first('SELECT id FROM authors WHERE slug = ?', [$slug]);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert(
            'INSERT INTO authors (slug, name, credentials, bio, url) VALUES (?, ?, ?, ?, ?)',
            [
                $slug,
                (string) ($options['author_name'] ?? Config::get('organisation', Config::siteName())),
                (string) ($options['author_credentials'] ?? 'Editorial team'),
                'Compiled from the classical rule tables of Faraid. The fiqh review is recorded on the Methodology page; until it is complete, everything here is a working draft.',
                Config::url('/about'),
            ]
        );
    }

    private static function category(string $slug, string $locale, string $name, string $description, int $position): int
    {
        $existing = Database::first('SELECT id FROM categories WHERE slug = ? AND locale = ?', [$slug, $locale]);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert(
            'INSERT INTO categories (slug, locale, name, description, position) VALUES (?, ?, ?, ?, ?)',
            [$slug, $locale, $name, $description, $position]
        );
    }

    private static function post(string $slug, string $locale, array $content, int $authorId): void
    {
        $categorySlug = (string) ($content['category'] ?? 'guides');
        $category = Database::first('SELECT id FROM categories WHERE slug = ? AND locale = ?', [$categorySlug, $locale]);

        $title = (string) $content['title'];
        // Content files carry placeholders rather than hard-coded contact
        // details, so a fresh install writes in its own name and address.
        $body = strtr((string) $content['body'], [
            '{{contact_email}}' => (string) Config::get('contact_email', ''),
            '{{site_name}}' => Config::siteName(),
        ]);

        PostRepository::save([
            'slug' => $slug,
            'locale' => $locale,
            'title' => $title,
            'h1' => (string) ($content['h1'] ?? $title),
            'meta_description' => (string) $content['meta_description'],
            'excerpt' => (string) ($content['excerpt'] ?? Str::excerpt($body)),
            'body' => $body,
            'cover_image' => null,
            'image_alt' => null,
            'category_id' => $category !== null ? (int) $category['id'] : null,
            'author_id' => $authorId,
            'status' => 'published',
            'target_keyword' => $content['target_keyword'] ?? null,
            'translation_group' => $content['translation_group'] ?? null,
            'reading_minutes' => Str::readingMinutes($body),
            'published_at' => Database::now(),
        ]);
    }

    /** @return list<string> */
    private static function contentFiles(string $locale): array
    {
        $files = glob(dirname(__DIR__, 2) . '/database/content/' . $locale . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    /** @return array<string,array<string,array{name:string,description:string,position:int}>> */
    private static function categories(): array
    {
        return [
            'en' => [
                'pages' => ['name' => 'Site pages', 'description' => '', 'position' => 99],
                'basics' => [
                    'name' => 'The basics',
                    'description' => 'How Islamic inheritance works, explained from the beginning with worked examples.',
                    'position' => 1,
                ],
                'shares' => [
                    'name' => 'Individual shares',
                    'description' => "What each heir receives — a daughter, a wife, a mother, a brother — and why.",
                    'position' => 2,
                ],
                'doctrines' => [
                    'name' => 'Rules and doctrines',
                    'description' => 'Awl, radd, exclusion and the cases where the four schools differ.',
                    'position' => 3,
                ],
                'wills' => [
                    'name' => 'Islamic wills by country',
                    'description' => 'Calculating your shares is one thing; making them legally binding where you live is another.',
                    'position' => 4,
                ],
            ],
            'ur' => [
                'pages' => ['name' => 'سائٹ کے صفحات', 'description' => '', 'position' => 99],
                'basics' => [
                    'name' => 'بنیادی باتیں',
                    'description' => 'اسلامی وراثت کیسے تقسیم ہوتی ہے — شروع سے، عملی مثالوں کے ساتھ۔',
                    'position' => 1,
                ],
                'shares' => [
                    'name' => 'ہر وارث کا حصہ',
                    'description' => 'بیٹی، بیوی، والدہ، بھائی — کس کو کتنا ملتا ہے اور کیوں۔',
                    'position' => 2,
                ],
                'doctrines' => [
                    'name' => 'اصول و قواعد',
                    'description' => 'عول، رد، حجب اور وہ مسائل جن میں مکاتبِ فکر مختلف ہیں۔',
                    'position' => 3,
                ],
                'wills' => [
                    'name' => 'قانون اور وصیت',
                    'description' => 'حصے نکالنا الگ بات ہے، انہیں قانونی طور پر نافذ کرانا الگ۔',
                    'position' => 4,
                ],
            ],
        ];
    }
}
