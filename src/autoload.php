<?php

declare(strict_types=1);

/**
 * Dependency-free PSR-4 autoloader for the Faraid namespace.
 *
 * The engine must be usable, and its tests runnable, on a plain PHP host with
 * no Composer install. Where Composer is available, use vendor/autoload.php
 * instead; the two are interchangeable.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Faraid\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/Faraid/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
