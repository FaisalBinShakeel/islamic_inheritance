<?php

declare(strict_types=1);

/**
 * Front controller. Every request that is not a real file lands here.
 */

// Under `php -S host:port -t public public/index.php`, every request reaches
// this script, including ones for real files. Apache and nginx serve those
// themselves; the built-in server needs telling.
if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($requested) && $requested !== '/' && is_file(__DIR__ . $requested)) {
        return false;
    }
}

require dirname(__DIR__) . '/src/autoload.php';

use App\Kernel;

Kernel::boot();

Kernel::handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
    $_GET,
    $_POST
)->send();
