<?php

namespace Core;

class Auth
{
    private static string $sessionKey = '_auth_user';

    public static function attempt(string $email, string $password): bool
    {
        $db   = Database::getInstance();
        $user = $db->selectOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = 'active'
             LIMIT 1",
            [$email]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        static::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        unset($user['password']);
        Session::set(static::$sessionKey, $user);
        Session::set('_last_activity', time());
    }

    public static function logout(): void
    {
        Session::forget(static::$sessionKey);
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has(static::$sessionKey);
    }

    public static function guest(): bool
    {
        return !static::check();
    }

    public static function user(): ?array
    {
        return Session::get(static::$sessionKey);
    }

    public static function id(): ?int
    {
        $user = static::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = static::user();
        return $user['role_slug'] ?? null;
    }

    public static function hasRole(string $role): bool
    {
        return static::role() === $role;
    }

    public static function isAdmin(): bool
    {
        return static::hasRole('admin');
    }

    public static function isSupervisorOrAdmin(): bool
    {
        return static::hasRole('admin') || static::hasRole('supervisor');
    }

    public static function hasFeature(string $feature): bool
    {
        // Dev mode: no LICENSE_API_URL configured → allow all features
        $apiUrl = config('app.license_api_url', env('LICENSE_API_URL', ''));
        $key    = config('app.license_key',     env('LICENSE_KEY', ''));
        if (!$apiUrl || !$key) {
            return true;
        }

        $license  = \App\Services\LicenseService::check();
        $features = $license['features'] ?? [];
        return in_array($feature, $features, true);
    }

    public static function refreshUser(): void
    {
        $id = static::id();
        if (!$id) return;

        $db   = Database::getInstance();
        $user = $db->selectOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1",
            [$id]
        );

        if ($user) {
            unset($user['password']);
            Session::set(static::$sessionKey, $user);
        }
    }
}
