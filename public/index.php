<?php

declare(strict_types=1);

/**
 * Front controller. Every request that is not a real file lands here.
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Kernel;

Kernel::boot();

Kernel::handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
    $_GET,
    $_POST
)->send();
