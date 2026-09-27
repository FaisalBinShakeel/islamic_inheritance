<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use App\Locale;

/**
 * Reads and writes posts.
 *
 * Trust pages (About, Methodology, Sources, Disclaimer, Contact) are posts
 * too, in the reserved "pages" category. One content system means one place
 * where the SEO rules are enforced, rather than a second, sloppier one for
 * the pages nobody thought of as content.
 */
final class PostRepository
{
    public const PAGE_CATEGORY = 'pages';

    private const SELECT = 'SELECT p.*, c.slug AS category_slug, c.name AS category_name,
                a.name AS author_name, a.slug AS author_slug, a.credentials AS author_credentials,
                a.bio AS author_bio, a.url AS author_url
            FROM posts p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN authors a ON a.id = p.author_id';

    public static function findBySlug(string $slug, ?string $locale = null, bool $publishedOnly = true): ?array
    {
        $sql = self::SELECT . ' WHERE p.slug = ? AND p.locale = ?';
        $params = [$slug, $locale ?? Locale::active()];

        if ($publishedOnly) {
            $sql .= ' AND p.status = ? AND p.published_at <= ?';
            $params[] = 'published';
            $params[] = Database::now();
        }

        return Database::first($sql . ' LIMIT 1', $params);
    }

    public static function findById(int $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE p.id = ? LIMIT 1', [$id]);
    }

    /** @return list<array<string,mixed>> */
    public static function published(?string $locale = null, int $limit = 12, int $offset = 0, ?string $categorySlug = null): array
    {
        $params = [$locale ?? Locale::active(), 'published', Database::now(), self::PAGE_CATEGORY];
        $sql = self::SELECT . ' WHERE p.locale = ? AND p.status = ? AND p.published_at <= ?
                AND (c.slug IS NULL OR c.slug <> ?)';

        if ($categorySlug !== null) {
            $sql .= ' AND c.slug = ?';
            $params[] = $categorySlug;
        }

        $sql .= ' ORDER BY p.published_at DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::all($sql, $params);
    }

    public static function countPublished(?string $locale = null, ?string $categorySlug = null): int
    {
        $params = [$locale ?? Locale::active(), 'published', Database::now(), self::PAGE_CATEGORY];
        $sql = 'SELECT COUNT(*) FROM posts p LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.locale = ? AND p.status = ? AND p.published_at <= ?
                AND (c.slug IS NULL OR c.slug <> ?)';

        if ($categorySlug !== null) {
            $sql .= ' AND c.slug = ?';
            $params[] = $categorySlug;
        }

        return (int) Database::value($sql, $params);
    }

    /** Sibling articles for contextual internal linking. */
    public static function related(array $post, int $limit = 3): array
    {
        $rows = Database::all(
            self::SELECT . ' WHERE p.locale = ? AND p.status = ? AND p.published_at <= ? AND p.id <> ?
                AND (c.slug IS NULL OR c.slug <> ?)
                ORDER BY CASE WHEN p.category_id = ? THEN 0 ELSE 1 END, p.published_at DESC
                LIMIT ' . max(1, $limit),
            [
                $post['locale'], 'published', Database::now(), (int) $post['id'],
                self::PAGE_CATEGORY, (int) ($post['category_id'] ?? 0),
            ]
        );

        return $rows;
    }

    /**
     * The same article in other locales, which is what drives the reciprocal
     * hreflang tags.
     *
     * @return array<string,string> locale => slug
     */
    public static function translations(array $post): array
    {
        $group = $post['translation_group'] ?? null;
        if ($group === null || $group === '') {
            return [$post['locale'] => $post['slug']];
        }

        $rows = Database::all(
            'SELECT locale, slug FROM posts WHERE translation_group = ? AND status = ? AND published_at <= ?',
            [$group, 'published', Database::now()]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['locale']] = (string) $row['slug'];
        }

        return $map === [] ? [$post['locale'] => $post['slug']] : $map;
    }

    /** @return list<array{loc:string,lastmod:string}> */
    public static function sitemapEntries(): array
    {
        $rows = Database::all(
            'SELECT p.slug, p.locale, p.updated_at, c.slug AS category_slug
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = ? AND p.published_at <= ?
             ORDER BY p.updated_at DESC',
            ['published', Database::now()]
        );

        $entries = [];
        foreach ($rows as $row) {
            $isPage = ($row['category_slug'] ?? null) === self::PAGE_CATEGORY;
            $path = $isPage
                ? Locale::path((string) $row['slug'], (string) $row['locale'])
                : Locale::path('blog/' . $row['slug'], (string) $row['locale']);

            $entries[] = ['loc' => $path, 'lastmod' => (string) $row['updated_at']];
        }

        return $entries;
    }

    public static function lastUpdated(): ?string
    {
        $value = Database::value('SELECT MAX(updated_at) FROM posts WHERE status = ?', ['published']);

        return $value === null ? null : (string) $value;
    }

    /** @return list<array<string,mixed>> */
    public static function categories(?string $locale = null): array
    {
        return Database::all(
            'SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id AND p.status = ?) AS post_count
             FROM categories c WHERE c.locale = ? AND c.slug <> ? ORDER BY c.position, c.name',
            ['published', $locale ?? Locale::active(), self::PAGE_CATEGORY]
        );
    }

    public static function category(string $slug, ?string $locale = null): ?array
    {
        return Database::first(
            'SELECT * FROM categories WHERE slug = ? AND locale = ? LIMIT 1',
            [$slug, $locale ?? Locale::active()]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function tagsFor(int $postId): array
    {
        return Database::all(
            'SELECT t.* FROM tags t INNER JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ? ORDER BY t.name',
            [$postId]
        );
    }

    /** @return list<array<string,mixed>> admin listing, every status */
    public static function adminList(int $limit = 50, int $offset = 0, ?string $locale = null): array
    {
        $params = [];
        $sql = self::SELECT;
        if ($locale !== null) {
            $sql .= ' WHERE p.locale = ?';
            $params[] = $locale;
        }
        $sql .= ' ORDER BY p.updated_at DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::all($sql, $params);
    }

    public static function slugExists(string $slug, string $locale, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = ? AND locale = ?';
        $params = [$slug, $locale];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return (int) Database::value($sql, $params) > 0;
    }

    public static function keywordTakenBy(string $keyword, string $locale, ?int $exceptId = null): ?array
    {
        if ($keyword === '') {
            return null;
        }
        $sql = 'SELECT id, slug, title FROM posts WHERE target_keyword = ? AND locale = ?';
        $params = [$keyword, $locale];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return Database::first($sql . ' LIMIT 1', $params);
    }

    public static function save(array $data, ?int $id = null): int
    {
        $now = Database::now();
        $columns = [
            'slug', 'locale', 'title', 'h1', 'meta_description', 'excerpt', 'body',
            'cover_image', 'image_alt', 'category_id', 'author_id', 'status',
            'target_keyword', 'translation_group', 'reading_minutes', 'published_at',
        ];

        $values = [];
        foreach ($columns as $column) {
            $values[] = $data[$column] ?? null;
        }

        if ($id === null) {
            $placeholders = implode(', ', array_fill(0, count($columns) + 2, '?'));
            $sql = 'INSERT INTO posts (' . implode(', ', $columns) . ', created_at, updated_at) VALUES (' . $placeholders . ')';
            $values[] = $now;
            $values[] = $now;

            return Database::insert($sql, $values);
        }

        $assignments = implode(' = ?, ', $columns) . ' = ?';
        $values[] = $now;
        $values[] = $id;
        Database::run('UPDATE posts SET ' . $assignments . ', updated_at = ? WHERE id = ?', $values);

        return $id;
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM post_tags WHERE post_id = ?', [$id]);
        Database::run('DELETE FROM posts WHERE id = ?', [$id]);
    }
}
