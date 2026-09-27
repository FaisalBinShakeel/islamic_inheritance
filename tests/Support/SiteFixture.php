<?php

declare(strict_types=1);

namespace Faraid\Tests\Support;

use App\Config;
use App\Database;
use App\Schema;
use App\Seeder;

/**
 * Installs a throwaway copy of the whole site into a temporary SQLite file,
 * so the route and SEO tests exercise the real content rather than fixtures
 * invented for the test.
 */
final class SiteFixture
{
    private static ?string $configPath = null;

    public static function boot(): void
    {
        if (self::$configPath !== null) {
            Config::load(self::$configPath);

            return;
        }

        $directory = sys_get_temp_dir() . '/wirasat-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0775, true);

        $databasePath = $directory . '/site.sqlite';
        $configPath = $directory . '/config.php';

        Config::write($configPath, [
            'url' => 'https://example.test',
            'site_name' => 'Wirasat Calculator',
            'organisation' => 'Wirasat Calculator',
            'db' => [
                'driver' => 'sqlite', 'path' => $databasePath,
                'host' => '', 'port' => 0, 'name' => 'sqlite', 'user' => '', 'pass' => '', 'charset' => 'utf8mb4',
            ],
            'locales' => ['en', 'ur'],
            'currency' => 'PKR',
            'contact_email' => 'test@example.test',
            'engine_unreviewed' => true,
            'debug' => false,
        ]);

        Config::load($configPath);

        $pdo = Database::connect(Database::settings());
        Schema::migrate($pdo);
        Database::set($pdo);
        Seeder::run($pdo);

        self::$configPath = $configPath;
    }
}
