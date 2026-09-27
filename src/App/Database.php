<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * One lazily-opened PDO connection.
 *
 * Two drivers are supported on purpose. MySQL is the production default, and
 * SQLite exists so the site can be installed on any PHP host by uploading the
 * files — no database to create, no credentials to find. The schema is
 * generated per driver in Schema.php rather than kept as one .sql file that
 * only runs on one of them.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        return self::$pdo = self::connect(self::settings());
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        return [
            'driver' => (string) Config::get('db.driver', 'mysql'),
            'host' => (string) Config::get('db.host', '127.0.0.1'),
            'port' => (int) Config::get('db.port', 3306),
            'name' => (string) Config::get('db.name', ''),
            'user' => (string) Config::get('db.user', ''),
            'pass' => (string) Config::get('db.pass', ''),
            'charset' => (string) Config::get('db.charset', 'utf8mb4'),
            'path' => (string) Config::get('db.path', ''),
        ];
    }

    public static function connect(array $settings): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if (($settings['driver'] ?? 'mysql') === 'sqlite') {
            $path = (string) $settings['path'];
            $directory = dirname($path);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');

            return $pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $settings['host'],
            (int) $settings['port'],
            $settings['name'],
            $settings['charset'] ?: 'utf8mb4'
        );

        return new PDO($dsn, (string) $settings['user'], (string) $settings['pass'], $options);
    }

    public static function driver(): string
    {
        return (string) self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function set(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /** @return list<array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function first(string $sql, array $params = []): ?array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function run(string $sql, array $params = []): int
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);

        return (int) self::connection()->lastInsertId();
    }

    /** Current time in the format the whole application stores. */
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
