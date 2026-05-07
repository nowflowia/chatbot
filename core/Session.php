<?php

namespace Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (static::$started || session_status() === PHP_SESSION_ACTIVE) {
            static::$started = true;
            return;
        }

        // Use a guaranteed-writable directory inside storage/ to avoid
        // cPanel/shared-hosting environments where /tmp is not writable
        // or is not shared across PHP worker processes.
        if (defined('ROOT_PATH')) {
            $savePath = ROOT_PATH . '/storage/sessions';
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0755, true);
            }
            if (is_dir($savePath) && is_writable($savePath)) {
                session_save_path($savePath);
            }
        }

        $secure   = config('app.session.secure', false);
        $lifetime = config('app.session.lifetime', 120) * 60;

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('CHATBOT_SESSION');
        session_start();
        static::$started = true;
    }

    public static function set(string $key, mixed $value): void
    {
        static::start();
        $keys = explode('.', $key);
        $data = &$_SESSION;
        foreach ($keys as $k) {
            if (!isset($data[$k]) || !is_array($data[$k])) {
                $data[$k] = [];
            }
            $data = &$data[$k];
        }
        $data = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        static::start();
        $keys = explode('.', $key);
        $data = $_SESSION;
        foreach ($keys as $k) {
            if (!is_array($data) || !array_key_exists($k, $data)) {
                return $default;
            }
            $data = $data[$k];
        }
        return $data;
    }

    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    public static function forget(string $key): void
    {
        static::start();
        $keys  = explode('.', $key);
        $data  = &$_SESSION;
        $last  = array_pop($keys);
        foreach ($keys as $k) {
            if (!isset($data[$k])) {
                return;
            }
            $data = &$data[$k];
        }
        unset($data[$last]);
    }

    public static function flash(string $key, mixed $value): void
    {
        static::set('_flash.' . $key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = static::get('_flash.' . $key, $default);
        static::forget('_flash.' . $key);
        return $value;
    }

    public static function destroy(): void
    {
        static::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        static::$started = false;
    }

    public static function regenerate(): void
    {
        static::start();
        session_regenerate_id(true);
    }

    public static function all(): array
    {
        static::start();
        return $_SESSION;
    }
}
