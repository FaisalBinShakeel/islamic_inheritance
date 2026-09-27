<?php

declare(strict_types=1);

namespace App;

/** Session-backed CSRF tokens for the admin forms and the error-report form. */
final class Csrf
{
    public static function token(): string
    {
        self::start();
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    public static function check(?string $token): bool
    {
        self::start();
        $expected = $_SESSION['csrf'] ?? null;

        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
            ]);
            session_start();
        }
    }
}
