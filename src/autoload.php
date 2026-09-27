<?php

declare(strict_types=1);

/**
 * Dependency-free PSR-4 autoloader.
 *
 * The site must run on a plain PHP host with no Composer install — upload the
 * files and it works. Where Composer is available, vendor/autoload.php does
 * the same job and the two are interchangeable.
 */
spl_autoload_register(static function (string $class): void {
    static $prefixes = [
        'Faraid\\' => __DIR__ . '/Faraid/',
        'App\\' => __DIR__ . '/App/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $path = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }

        return;
    }
});

require_once __DIR__ . '/App/helpers.php';
