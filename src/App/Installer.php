<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * The web installer.
 *
 * Upload the files, open the site, answer a few questions. It checks the
 * environment first, then creates the tables, seeds the starting content and
 * writes config.php — the same experience as installing any ordinary PHP
 * application.
 */
final class Installer
{
    public const MIN_PHP = '8.2.0';

    public static function configPath(): string
    {
        return dirname(__DIR__, 2) . '/config.php';
    }

    public static function defaultSqlitePath(): string
    {
        return dirname(__DIR__, 2) . '/database/site.sqlite';
    }

    /** @return list<array{name:string,ok:bool,detail:string,fatal:bool}> */
    public static function requirements(): array
    {
        $root = dirname(__DIR__, 2);
        $checks = [];

        $checks[] = [
            'name' => 'PHP ' . self::MIN_PHP . ' or newer',
            'ok' => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
            'detail' => 'Running PHP ' . PHP_VERSION,
            'fatal' => true,
        ];

        foreach (['mbstring', 'json', 'pdo'] as $extension) {
            $checks[] = [
                'name' => 'Extension: ' . $extension,
                'ok' => extension_loaded($extension),
                'detail' => extension_loaded($extension) ? 'Loaded' : 'Not loaded',
                'fatal' => true,
            ];
        }

        $drivers = PDO::getAvailableDrivers();
        $checks[] = [
            'name' => 'A database driver (MySQL or SQLite)',
            'ok' => in_array('mysql', $drivers, true) || in_array('sqlite', $drivers, true),
            'detail' => 'Available: ' . (implode(', ', $drivers) ?: 'none'),
            'fatal' => true,
        ];

        $configWritable = is_writable(self::configPath()) || is_writable($root);
        $checks[] = [
            'name' => 'config.php can be written',
            'ok' => $configWritable,
            'detail' => $configWritable
                ? 'The project directory is writable'
                : 'Make ' . $root . ' writable, or create config.php by hand from config.example.php',
            'fatal' => false,
        ];

        $databaseDirectory = $root . '/database';
        $sqliteWritable = is_dir($databaseDirectory) && is_writable($databaseDirectory);
        $checks[] = [
            'name' => 'database/ is writable (needed only for SQLite)',
            'ok' => $sqliteWritable,
            'detail' => $sqliteWritable ? 'Writable' : 'Make ' . $databaseDirectory . ' writable to use SQLite',
            'fatal' => false,
        ];

        return $checks;
    }

    public static function requirementsMet(): bool
    {
        foreach (self::requirements() as $check) {
            if ($check['fatal'] && !$check['ok']) {
                return false;
            }
        }

        return true;
    }

    /** @return array{ok:bool,message:string} */
    public static function testDatabase(array $settings): array
    {
        try {
            $pdo = Database::connect(self::normaliseDatabase($settings));
            $pdo->query('SELECT 1');

            return ['ok' => true, 'message' => 'Connected.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create everything and write the config.
     *
     * @return array{ok:bool,message:string,report:array}
     */
    public static function install(array $form): array
    {
        $database = self::normaliseDatabase($form);

        $url = rtrim(trim((string) ($form['url'] ?? '')), '/');
        if ($url === '') {
            $url = self::guessUrl();
        }
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $email = trim((string) ($form['admin_email'] ?? ''));
        $password = (string) ($form['admin_password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Enter a valid email address for the administrator.', 'report' => []];
        }
        if (mb_strlen($password) < 10) {
            return ['ok' => false, 'message' => 'The administrator password must be at least 10 characters.', 'report' => []];
        }

        $locales = array_values(array_filter(
            (array) ($form['locales'] ?? ['en']),
            static fn ($code) => is_string($code) && $code !== ''
        ));
        if (!in_array('en', $locales, true)) {
            array_unshift($locales, 'en');
        }

        $values = [
            'url' => $url,
            'site_name' => trim((string) ($form['site_name'] ?? '')) ?: 'Wirasat Calculator',
            'organisation' => trim((string) ($form['organisation'] ?? '')) ?: (trim((string) ($form['site_name'] ?? '')) ?: 'Wirasat Calculator'),
            'db' => $database,
            'locales' => $locales,
            'currency' => trim((string) ($form['currency'] ?? '')),
            'contact_email' => $email,
            'default_madhhab' => null,
            'google_site_verification' => '',
            'bing_site_verification' => '',
            'engine_unreviewed' => true,
            'debug' => false,
        ];

        try {
            $pdo = Database::connect($database);
            Schema::migrate($pdo);
            Database::set($pdo);

            // The config has to be live before seeding, because the seeded
            // content contains absolute URLs and locale paths.
            Config::write(self::configPath(), $values);
            Config::load(self::configPath());

            self::createAdmin($pdo, $email, $password, (string) ($form['admin_name'] ?? 'Administrator'));
            $report = Seeder::run($pdo, ['author_name' => (string) ($form['admin_name'] ?? '')]);

            Database::run('INSERT INTO settings (name, value, updated_at) VALUES (?, ?, ?)', [
                'schema_version', (string) Schema::VERSION, Database::now(),
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'report' => []];
        }

        return ['ok' => true, 'message' => 'Installed.', 'report' => $report];
    }

    private static function createAdmin(PDO $pdo, string $email, string $password, string $name): void
    {
        $existing = Database::first('SELECT id FROM admins WHERE email = ?', [$email]);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existing !== null) {
            Database::run('UPDATE admins SET password_hash = ?, name = ? WHERE id = ?', [$hash, $name ?: 'Administrator', (int) $existing['id']]);

            return;
        }

        Database::run(
            'INSERT INTO admins (email, name, password_hash, created_at) VALUES (?, ?, ?, ?)',
            [$email, $name ?: 'Administrator', $hash, Database::now()]
        );
    }

    /** @return array<string,mixed> */
    private static function normaliseDatabase(array $form): array
    {
        $driver = ($form['driver'] ?? 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';

        if ($driver === 'sqlite') {
            $path = trim((string) ($form['db_path'] ?? ''));

            return [
                'driver' => 'sqlite',
                'path' => $path !== '' ? $path : self::defaultSqlitePath(),
                'host' => '', 'port' => 0, 'name' => 'sqlite', 'user' => '', 'pass' => '', 'charset' => 'utf8mb4',
            ];
        }

        return [
            'driver' => 'mysql',
            'host' => trim((string) ($form['db_host'] ?? '127.0.0.1')) ?: '127.0.0.1',
            'port' => (int) ($form['db_port'] ?? 3306) ?: 3306,
            'name' => trim((string) ($form['db_name'] ?? '')),
            'user' => trim((string) ($form['db_user'] ?? '')),
            'pass' => (string) ($form['db_pass'] ?? ''),
            'charset' => 'utf8mb4',
            'path' => '',
        ];
    }

    public static function guessUrl(): string
    {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return ($https ? 'https://' : 'http://') . $host;
    }
}
