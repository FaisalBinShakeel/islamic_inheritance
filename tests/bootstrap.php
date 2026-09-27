<?php

declare(strict_types=1);

/** Works with or without a Composer install. */
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';

    return;
}

require __DIR__ . '/../src/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Faraid\\Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
