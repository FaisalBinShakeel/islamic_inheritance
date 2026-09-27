<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * The database schema, generated per driver.
 *
 * Two rules from the product specification are enforced here rather than in a
 * checklist, because a checklist is something a person can skip:
 *
 *  - `title` (the <title> tag) and `h1` are separate columns. The admin screen
 *    shows them side by side and warns when they diverge.
 *  - `target_keyword` is unique per locale, so two posts cannot chase the same
 *    primary phrase.
 */
final class Schema
{
    /** Bumped whenever a migration is added. */
    public const VERSION = 1;

    public static function migrate(PDO $pdo): void
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        foreach (self::statements($driver) as $sql) {
            $pdo->exec($sql);
        }
    }

    /** @return list<string> */
    public static function statements(string $driver): array
    {
        $sqlite = $driver === 'sqlite';

        $id = $sqlite
            ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
            : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $suffix = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $text = $sqlite ? 'TEXT' : 'LONGTEXT';
        $fk = $sqlite ? 'INTEGER' : 'INT UNSIGNED';

        $statements = [];

        $statements[] = "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(64) NOT NULL PRIMARY KEY,
            value $text NULL,
            updated_at VARCHAR(19) NULL
        )$suffix";

        $statements[] = "CREATE TABLE IF NOT EXISTS admins (
            id $id,
            email VARCHAR(190) NOT NULL,
            name VARCHAR(120) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at VARCHAR(19) NOT NULL,
            last_login_at VARCHAR(19) NULL
        )$suffix";
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS admins_email ON admins (email)';

        $statements[] = "CREATE TABLE IF NOT EXISTS authors (
            id $id,
            slug VARCHAR(120) NOT NULL,
            name VARCHAR(160) NOT NULL,
            credentials VARCHAR(255) NULL,
            bio $text NULL,
            photo VARCHAR(255) NULL,
            url VARCHAR(255) NULL
        )$suffix";
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS authors_slug ON authors (slug)';

        $statements[] = "CREATE TABLE IF NOT EXISTS categories (
            id $id,
            slug VARCHAR(120) NOT NULL,
            locale VARCHAR(8) NOT NULL,
            name VARCHAR(160) NOT NULL,
            description $text NULL,
            position INTEGER NOT NULL DEFAULT 0
        )$suffix";
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS categories_slug_locale ON categories (slug, locale)';

        $statements[] = "CREATE TABLE IF NOT EXISTS tags (
            id $id,
            slug VARCHAR(120) NOT NULL,
            locale VARCHAR(8) NOT NULL,
            name VARCHAR(160) NOT NULL
        )$suffix";
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS tags_slug_locale ON tags (slug, locale)';

        $statements[] = "CREATE TABLE IF NOT EXISTS posts (
            id $id,
            slug VARCHAR(190) NOT NULL,
            locale VARCHAR(8) NOT NULL,
            title VARCHAR(255) NOT NULL,
            h1 VARCHAR(255) NOT NULL,
            meta_description VARCHAR(255) NOT NULL,
            excerpt $text NULL,
            body $text NOT NULL,
            cover_image VARCHAR(255) NULL,
            image_alt VARCHAR(255) NULL,
            category_id $fk NULL,
            author_id $fk NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'draft',
            target_keyword VARCHAR(190) NULL,
            translation_group VARCHAR(120) NULL,
            reading_minutes INTEGER NOT NULL DEFAULT 1,
            published_at VARCHAR(19) NULL,
            created_at VARCHAR(19) NOT NULL,
            updated_at VARCHAR(19) NOT NULL
        )$suffix";
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS posts_slug_locale ON posts (slug, locale)';
        // No two posts may target the same primary phrase in one locale.
        $statements[] = 'CREATE UNIQUE INDEX IF NOT EXISTS posts_keyword_locale ON posts (target_keyword, locale)';
        $statements[] = 'CREATE INDEX IF NOT EXISTS posts_status_published ON posts (status, published_at)';
        $statements[] = 'CREATE INDEX IF NOT EXISTS posts_translation_group ON posts (translation_group)';

        $statements[] = "CREATE TABLE IF NOT EXISTS post_tags (
            post_id $fk NOT NULL,
            tag_id $fk NOT NULL,
            PRIMARY KEY (post_id, tag_id)
        )$suffix";

        $statements[] = "CREATE TABLE IF NOT EXISTS calculator_events (
            id $id,
            locale VARCHAR(8) NOT NULL,
            madhhab VARCHAR(16) NULL,
            event VARCHAR(32) NOT NULL,
            heir_signature VARCHAR(255) NULL,
            created_at VARCHAR(19) NOT NULL
        )$suffix";
        $statements[] = 'CREATE INDEX IF NOT EXISTS calculator_events_created ON calculator_events (created_at)';

        $statements[] = "CREATE TABLE IF NOT EXISTS error_reports (
            id $id,
            locale VARCHAR(8) NOT NULL,
            madhhab VARCHAR(16) NULL,
            heirs $text NULL,
            expected $text NULL,
            message $text NOT NULL,
            reporter_email VARCHAR(190) NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'open',
            created_at VARCHAR(19) NOT NULL
        )$suffix";

        return $statements;
    }
}
