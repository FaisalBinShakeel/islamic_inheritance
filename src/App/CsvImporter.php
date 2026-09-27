<?php

declare(strict_types=1);

namespace App;

use App\Repository\PostRepository;
use App\Support\Str;

/**
 * Bulk import of posts from a CSV file, with the publish rules applied to
 * every row before anything is written.
 *
 * Expected columns (header row required, order does not matter):
 *   Slug, Locale, Title, H1, MetaDescription, Category, Tags, Body,
 *   ImagePrompt, ImageAlt, Author, PublishAt, TargetKeyword, TranslationGroup
 *
 * A row that fails validation is reported and skipped; the rest still import.
 * Nothing is written until the whole file has been read, so a broken file
 * cannot leave the site half-updated.
 */
final class CsvImporter
{
    /** @return array{imported:int,skipped:int,rows:list<array{row:int,slug:string,status:string,messages:list<string>}>} */
    public static function import(string $path, bool $dryRun = false): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0, 'rows' => [[
                'row' => 0, 'slug' => '', 'status' => 'error', 'messages' => ['The file could not be opened.'],
            ]]];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'rows' => [[
                'row' => 0, 'slug' => '', 'status' => 'error', 'messages' => ['The file is empty.'],
            ]]];
        }

        $map = [];
        foreach ($header as $index => $name) {
            $map[self::normalise((string) $name)] = $index;
        }

        $rows = [];
        $accepted = [];
        $seenSlugs = [];
        $lineNumber = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($line === [null] || $line === []) {
                continue;
            }

            $get = static function (string $column, string $default = '') use ($map, $line): string {
                $index = $map[self::normalise($column)] ?? null;

                return $index === null ? $default : trim((string) ($line[$index] ?? ''));
            };

            $locale = $get('Locale', 'en') ?: 'en';
            $title = $get('Title');
            $slug = $get('Slug') !== '' ? $get('Slug') : Str::slug($title);
            $body = $get('Body');

            $post = [
                'slug' => $slug,
                'locale' => $locale,
                'title' => $title,
                'h1' => $get('H1') !== '' ? $get('H1') : $title,
                'meta_description' => $get('MetaDescription'),
                'body' => $body,
                'excerpt' => Str::excerpt($body),
                'image_alt' => $get('ImageAlt') ?: null,
                'cover_image' => $get('CoverImage') ?: null,
                'target_keyword' => $get('TargetKeyword') ?: null,
                'translation_group' => $get('TranslationGroup') ?: null,
                'reading_minutes' => Str::readingMinutes($body),
                'status' => 'published',
                'published_at' => self::publishAt($get('PublishAt')),
                '_category' => $get('Category'),
                '_tags' => $get('Tags'),
                '_author' => $get('Author'),
            ];

            $messages = [];
            $key = $locale . '/' . $slug;
            if (isset($seenSlugs[$key])) {
                $messages[] = sprintf('Duplicate slug "%s" within this file.', $slug);
            }
            $seenSlugs[$key] = true;

            $existing = PostRepository::findBySlug($slug, $locale, false);
            $check = PostValidator::check($post, $existing !== null ? (int) $existing['id'] : null);
            $messages = array_merge($messages, $check['errors']);

            if ($messages !== []) {
                $rows[] = ['row' => $lineNumber, 'slug' => $slug, 'status' => 'skipped', 'messages' => $messages];
                continue;
            }

            $rows[] = [
                'row' => $lineNumber,
                'slug' => $slug,
                'status' => $existing !== null ? 'update' : 'create',
                'messages' => $check['warnings'],
            ];
            $accepted[] = [$post, $existing !== null ? (int) $existing['id'] : null];
        }

        fclose($handle);

        $imported = 0;
        if (!$dryRun) {
            foreach ($accepted as [$post, $id]) {
                $post['category_id'] = self::categoryId((string) $post['_category'], (string) $post['locale']);
                $post['author_id'] = self::authorId((string) $post['_author']);
                $tags = (string) $post['_tags'];
                unset($post['_category'], $post['_tags'], $post['_author']);

                $postId = PostRepository::save($post, $id);
                self::syncTags($postId, $tags, (string) $post['locale']);
                $imported++;
            }
        }

        return [
            'imported' => $dryRun ? 0 : $imported,
            'skipped' => count($rows) - count($accepted),
            'rows' => $rows,
        ];
    }

    private static function publishAt(string $value): string
    {
        if ($value === '') {
            return Database::now();
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? Database::now() : gmdate('Y-m-d H:i:s', $timestamp);
    }

    private static function categoryId(string $name, string $locale): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $slug = Str::slug($name);
        $existing = Database::first('SELECT id FROM categories WHERE slug = ? AND locale = ?', [$slug, $locale]);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert(
            'INSERT INTO categories (slug, locale, name, description, position) VALUES (?, ?, ?, ?, ?)',
            [$slug, $locale, $name, '', 50]
        );
    }

    private static function authorId(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            $fallback = Database::first('SELECT id FROM authors ORDER BY id LIMIT 1');

            return $fallback !== null ? (int) $fallback['id'] : null;
        }

        $slug = Str::slug($name);
        $existing = Database::first('SELECT id FROM authors WHERE slug = ?', [$slug]);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Database::insert('INSERT INTO authors (slug, name) VALUES (?, ?)', [$slug, $name]);
    }

    private static function syncTags(int $postId, string $tags, string $locale): void
    {
        Database::run('DELETE FROM post_tags WHERE post_id = ?', [$postId]);
        foreach (array_filter(array_map('trim', explode(',', $tags))) as $name) {
            $slug = Str::slug($name);
            $tag = Database::first('SELECT id FROM tags WHERE slug = ? AND locale = ?', [$slug, $locale]);
            $tagId = $tag !== null
                ? (int) $tag['id']
                : Database::insert('INSERT INTO tags (slug, locale, name) VALUES (?, ?, ?)', [$slug, $locale, $name]);

            Database::run('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
        }
    }

    private static function normalise(string $name): string
    {
        return strtolower(preg_replace('~[^a-z0-9]~i', '', $name) ?? '');
    }
}
