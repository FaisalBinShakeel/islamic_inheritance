<?php

declare(strict_types=1);

namespace App;

/** Session-based admin sign-in. One role: there is only an administrator. */
final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $admin = Database::first('SELECT * FROM admins WHERE email = ? LIMIT 1', [$email]);
        if ($admin === null || !password_verify($password, (string) $admin['password_hash'])) {
            // Same cost whether or not the account exists.
            password_verify($password, '$2y$12$usesomesillystringforeucaOllySpxZFNeK7qcbXDzWLTs5yEm');

            return false;
        }

        if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
            Database::run('UPDATE admins SET password_hash = ? WHERE id = ?', [
                password_hash($password, PASSWORD_DEFAULT), (int) $admin['id'],
            ]);
        }

        Csrf::start();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = (string) $admin['name'];

        Database::run('UPDATE admins SET last_login_at = ? WHERE id = ?', [Database::now(), (int) $admin['id']]);

        return true;
    }

    public static function check(): bool
    {
        Csrf::start();

        return isset($_SESSION['admin_id']);
    }

    public static function name(): string
    {
        Csrf::start();

        return (string) ($_SESSION['admin_name'] ?? 'Admin');
    }

    public static function id(): ?int
    {
        Csrf::start();

        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function logout(): void
    {
        Csrf::start();
        $_SESSION = [];
        session_destroy();
    }
}
