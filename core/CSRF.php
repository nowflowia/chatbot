<?php

namespace Core;

class CSRF
{
    private static string $sessionKey = '_csrf_token';
    private static int $tokenLength   = 64;

    public static function token(): string
    {
        Session::start();
        if (!Session::has(static::$sessionKey)) {
            Session::set(static::$sessionKey, generate_token(static::$tokenLength));
        }
        return Session::get(static::$sessionKey);
    }

    public static function verify(string $token): bool
    {
        $sessionToken = Session::get(static::$sessionKey, '');
        return hash_equals($sessionToken, $token);
    }

    public static function check(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $token = $request->input('_csrf_token')
            ?? $request->header('X-CSRF-Token')
            ?? $request->header('X-XSRF-Token')
            ?? '';

        if (!static::verify($token)) {
            http_response_code(419);
            if ($request->isAjax()) {
                json_response(false, 'Token CSRF inválido.', null, [], 419);
            }
            die('Token CSRF inválido. <a href="javascript:history.back()">Voltar</a>');
        }

        return true;
    }

    public static function regenerate(): void
    {
        Session::set(static::$sessionKey, generate_token(static::$tokenLength));
    }
}
